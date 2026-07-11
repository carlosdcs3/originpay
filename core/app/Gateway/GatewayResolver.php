<?php

namespace App\Gateway;

use App\Enums\PaymentMethod;
use App\Enums\PaymentOperation;
use App\Enums\RoutingStrategy;
use App\Models\PaymentGateway;
use App\Models\PaymentMethodRoute;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GatewayResolver
{
    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    | Two entry points:
    |   1. resolveAllForCharge()  — Legacy; accepts PaymentMethod enum.
    |                               Bridges to resolveForOperation() transparently.
    |   2. resolveForOperation()  — New preferred method; accepts PaymentOperation.
    |
    | A third method, resolveForWithdrawal(), is stubbed for the Withdrawal module.
    */

    /**
     * [LEGACY COMPAT] Resolve gateways for a charge given a PaymentMethod.
     *
     * Internally maps PaymentMethod → PaymentOperation (primary) and delegates
     * to resolveForOperation(). The public signature is unchanged, ensuring
     * zero breaking changes in ChargeService and any other callers.
     */
    public static function resolveAllForCharge(User $user, PaymentMethod $paymentMethod): Collection
    {
        $operation = PaymentOperation::fromPaymentMethod($paymentMethod);
        return self::resolveForOperation($user, $operation);
    }

    /**
     * [PRIMARY] Resolve the ordered list of gateways for a specific payment operation.
     *
     * Pipeline:
     *   1. findRoute()              — Look up PaymentMethodRoute for this operation.
     *   2. buildGatewayChain()      — Build ordered list (primary + fallbacks).
     *   3. applyKycFilter()         — Enforce Mock/Prod rules based on KYC status.
     *   4. applyCircuitBreakerFilter() — Remove OPEN circuit gateways.
     *   5. applyStrategy()          — Apply routing strategy (currently only 'manual').
     *
     * Returns a Collection of PaymentGateway models in execution order.
     */
    public static function resolveForOperation(User $user, PaymentOperation $operation): Collection
    {
        // Step 1: Find configured route
        $route = self::findRoute($operation);

        // Step 2: Build the ordered gateway chain
        $gateways = self::buildGatewayChain($route, $operation);

        if ($gateways->isEmpty()) {
            throw new Exception("Nenhum adquirente ativo e compatível encontrado para a operação: {$operation->value}");
        }

        // Step 3: KYC filter (must happen before CB to ensure mock is applied correctly)
        $gateways = self::applyKycFilter($gateways, $user);

        if ($gateways->isEmpty()) {
            throw new Exception("Nenhum adquirente disponível após as regras de KYC e ambiente para a operação: {$operation->value}");
        }

        // Step 4: Circuit Breaker filter
        $gateways = self::applyCircuitBreakerFilter($gateways);

        if ($gateways->isEmpty()) {
            throw new Exception("Todos os adquirentes compatíveis estão inoperantes (Circuit Breaker OPEN) para a operação: {$operation->value}");
        }

        // Step 5: Apply routing strategy (currently only MANUAL does anything)
        $strategy = $route ? $route->getStrategyEnum() : RoutingStrategy::MANUAL;
        $gateways = self::applyStrategy($gateways, $strategy);

        return $gateways->values();
    }

    /**
     * [FUTURE — Withdrawal Module]
     *
     * Stub for resolving gateways for a PIX withdrawal (cash-out).
     * This method intentionally throws until the Withdrawal module is integrated.
     *
     * @future Connect this method to WithdrawalService when the module is built.
     *         The routing logic will mirror resolveForOperation() but scoped
     *         to the pix_withdraw PaymentOperation route.
     *
     * @throws Exception Always — not yet implemented.
     */
    public static function resolveForWithdrawal(User $user): Collection
    {
        throw new Exception(
            'GatewayResolver::resolveForWithdrawal() is not yet implemented. ' .
            'Configure the pix_withdraw operation in Admin > Gateways > Roteamento ' .
            'and connect this method when building the Withdrawal module.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Private Pipeline Steps
    |--------------------------------------------------------------------------
    */

    /**
     * Step 1: Find the PaymentMethodRoute for a given operation.
     * Falls back to the legacy payment_method lookup for backward compat.
     */
    private static function findRoute(PaymentOperation $operation): ?PaymentMethodRoute
    {
        if (! Schema::hasTable('payment_method_routes')) {
            Log::warning('Payment method routes table is missing; falling back to gateway priority queue.', [
                'operation' => $operation->value,
            ]);

            return null;
        }

        // New path: look up by payment_operation
        $route = PaymentMethodRoute::forOperation($operation);

        // Legacy fallback: if no operation-based route, try the old payment_method column
        if (!$route) {
            $legacyMethod = $operation->paymentMethod()->value;
            $route = PaymentMethodRoute::forLegacyMethod($legacyMethod);
        }

        return ($route && $route->enabled) ? $route : null;
    }

    /**
     * Step 2: Build the ordered gateway chain from the route, or fall back
     * to the global priority queue (legacy behavior).
     */
    private static function buildGatewayChain(?PaymentMethodRoute $route, PaymentOperation $operation): Collection
    {
        // ── Route-based chain (new preferred path) ───────────────────────────
        if ($route && $route->primary_gateway_id) {
            $orderedIds = [$route->primary_gateway_id];

            if (is_array($route->fallback_gateway_ids)) {
                $orderedIds = array_merge($orderedIds, $route->fallback_gateway_ids);
            }

            $routeGateways = PaymentGateway::whereIn('id', $orderedIds)
                ->active()
                ->where('is_maintenance', false)
                ->get()
                ->keyBy('id');

            $chain = collect();
            foreach ($orderedIds as $id) {
                if ($routeGateways->has($id)) {
                    $gw = $routeGateways->get($id);
                    if ($gw->{$operation->supportFlag()}) {
                        $chain->push($gw);
                    }
                }
            }

            if ($chain->isNotEmpty()) {
                return $chain;
            }
        }

        // ── Global priority fallback (legacy behavior, always available) ─────
        $query = PaymentGateway::active()
            ->where('is_maintenance', false)
            ->where($operation->supportFlag(), true)
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc');

        $gateways = $query->get();

        /** @var \App\Services\GatewayHealthScoreService $healthScore */
        $healthScore = app(\App\Services\GatewayHealthScoreService::class);

        return $gateways->sort(function ($a, $b) use ($healthScore) {
            if ($a->priority !== $b->priority) {
                return $a->priority <=> $b->priority;
            }
            return $healthScore->getScore($b->code) <=> $healthScore->getScore($a->code);
        })->values();
    }

    /**
     * Step 3: Enforce KYC and environment rules.
     * - Users without KYC approval are forced to the Mock gateway.
     * - Production environments never use the Mock gateway.
     */
    private static function applyKycFilter(Collection $gateways, User $user): Collection
    {
        $kycStatus = $user->kyc_status instanceof \App\Enums\KycStatus
            ? $user->kyc_status->value
            : $user->kyc_status;

        if ($kycStatus !== \App\Enums\KycStatus::APPROVED->value) {
            $mockGateway = PaymentGateway::where('code', 'mock')->first();
            if ($mockGateway) {
                return collect([$mockGateway]);
            }
            throw new Exception("Usuário pendente de KYC, porém o gateway Mock não está disponível.");
        }

        if (config('app.env') === 'production') {
            $gateways = $gateways->reject(fn ($gw) => $gw->code === 'mock');
        }

        return $gateways;
    }

    /**
     * Step 4: Remove gateways whose Circuit Breaker is in OPEN state.
     */
    private static function applyCircuitBreakerFilter(Collection $gateways): Collection
    {
        /** @var \App\Services\CircuitBreakerService $circuitBreaker */
        $circuitBreaker = app(\App\Services\CircuitBreakerService::class);

        return $gateways->reject(
            fn ($gw) => $circuitBreaker->getState($gw->code) === \App\Services\CircuitBreakerService::STATE_OPEN
        );
    }

    /**
     * Step 5: Apply the routing strategy to sort/select the final gateway chain.
     *
     * Currently only MANUAL is implemented. All other strategies preserve
     * the current order (safe default) until their logic is built.
     */
    private static function applyStrategy(Collection $gateways, RoutingStrategy $strategy): Collection
    {
        return match ($strategy) {
            RoutingStrategy::MANUAL => $gateways, // Order already set by buildGatewayChain

            // ── Future strategies — order is preserved as-is until implemented ──
            RoutingStrategy::LOWEST_FEE,
            RoutingStrategy::LOWEST_LATENCY,
            RoutingStrategy::HIGHEST_HEALTH_SCORE,
            RoutingStrategy::WEIGHTED,
            RoutingStrategy::ROUND_ROBIN => $gateways,
        };
    }
}
