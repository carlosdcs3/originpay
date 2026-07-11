<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PlatformFeeRule;
use App\Models\PlatformFeeRuleAudit;
use App\Models\User;
use App\Modules\Fees\Domain\Contracts\PlatformFeeSimulator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformFeeRuleController extends Controller
{
    private const METHODS = ['pix', 'card', 'boleto', 'crypto'];

    public function index()
    {
        $pageTitle = 'Taxas da Plataforma';
        $methods = self::METHODS;
        $globalRules = PlatformFeeRule::query()
            ->global()
            ->forMethodList($methods)
            ->latestEffective()
            ->get()
            ->groupBy('payment_method');
        $merchantRules = PlatformFeeRule::with('user')
            ->where('scope', PlatformFeeRule::SCOPE_MERCHANT)
            ->latest()
            ->paginate(15, ['*'], 'rules_page');
        $audits = PlatformFeeRuleAudit::with(['admin', 'rule.user'])
            ->latest()
            ->paginate(15, ['*'], 'audits_page');
        $merchants = User::query()
            ->select(['id', 'name', 'email', 'username'])
            ->orderBy('name')
            ->limit(250)
            ->get();

        return view('backend.platform_fees.index', compact(
            'pageTitle',
            'methods',
            'globalRules',
            'merchantRules',
            'audits',
            'merchants'
        ));
    }

    public function storeGlobal(Request $request)
    {
        $data = $this->validatedRuleData($request);
        $rule = $this->createRule($request, $data + [
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'user_id' => null,
        ]);

        return $this->storedResponse($request, $rule, 'Taxa global criada com sucesso.');
    }

    public function storeMerchant(Request $request)
    {
        $data = $this->validatedRuleData($request, [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        $rule = $this->createRule($request, $data + [
            'scope' => PlatformFeeRule::SCOPE_MERCHANT,
        ]);

        return $this->storedResponse($request, $rule, 'Taxa individual criada com sucesso.');
    }

    public function deactivate(Request $request, PlatformFeeRule $rule)
    {
        $oldValues = $rule->toArray();
        $rule->update([
            'status' => PlatformFeeRule::STATUS_INACTIVE,
            'updated_by_admin_id' => Auth::guard('admin')->id(),
        ]);

        $this->audit($request, $rule, 'deactivated', $oldValues, $rule->fresh()->toArray());

        return back()->withNotify([['success', 'Regra desativada com sucesso.']]);
    }

    public function simulate(Request $request, PlatformFeeSimulator $simulator)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'currency' => ['nullable', 'string', 'size:3'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'rule_id' => ['nullable', 'integer', 'exists:platform_fee_rules,id'],
        ]);

        $result = $simulator->simulate($data);

        if ($request->expectsJson()) {
            return response()->json($result->toArray());
        }

        return back()->withInput()->with('simulation', $result->toArray());
    }

    private function validatedRuleData(Request $request, array $extraRules = []): array
    {
        return $request->validate($extraRules + [
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'currency' => ['nullable', 'string', 'size:3'],
            'pricing_model' => ['nullable', Rule::in(['flat', 'tiered'])],
            'fixed_fee' => ['required', 'numeric', 'min:0'],
            'percentage_fee' => ['required', 'numeric', 'min:0', 'max:100'],
            'tiers' => ['nullable', 'array'],
            'tiers.*.from_amount' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.to_amount' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.percentage_fee' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_fee' => ['nullable', 'numeric', 'min:0'],
            'maximum_fee' => ['nullable', 'numeric', 'min:0'],
            'settlement_delay_days' => ['required', 'integer', 'min:0', 'max:365'],
            'reserve_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::in([PlatformFeeRule::STATUS_ACTIVE, PlatformFeeRule::STATUS_INACTIVE])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
    }

    private function createRule(Request $request, array $data): PlatformFeeRule
    {
        $pricingMetadata = $this->normalizePricingMetadata($data);

        $data['currency'] = strtoupper($data['currency'] ?? 'BRL');
        $data['payment_method'] = strtolower($data['payment_method']);
        $data['created_by_admin_id'] = Auth::guard('admin')->id();
        $data['updated_by_admin_id'] = Auth::guard('admin')->id();
        $data['metadata'] = ['reason' => $data['reason']] + $pricingMetadata;
        $reason = $data['reason'];
        unset($data['reason'], $data['pricing_model'], $data['tiers']);

        $rule = PlatformFeeRule::create($data);

        if ($rule->status === PlatformFeeRule::STATUS_ACTIVE) {
            PlatformFeeRule::query()
                ->where('id', '!=', $rule->id)
                ->where('scope', $rule->scope)
                ->where('user_id', $rule->user_id)
                ->where('payment_method', $rule->payment_method)
                ->where('currency', $rule->currency)
                ->where('status', PlatformFeeRule::STATUS_ACTIVE)
                ->update([
                    'status' => PlatformFeeRule::STATUS_INACTIVE,
                    'updated_by_admin_id' => Auth::guard('admin')->id(),
                    'updated_at' => now(),
                ]);
        }

        $this->audit($request, $rule, 'created', null, $rule->fresh()->toArray(), $reason);

        return $rule;
    }

    private function normalizePricingMetadata(array $data): array
    {
        $pricingModel = $data['pricing_model'] ?? 'flat';

        if ($pricingModel !== 'tiered') {
            return ['pricing_model' => 'flat'];
        }

        $tiers = collect($data['tiers'] ?? [])
            ->map(function (array $tier) {
                $fromAmount = $tier['from_amount'] ?? null;

                if ($fromAmount === null || $fromAmount === '') {
                    return null;
                }

                return [
                    'from_amount' => round((float) $fromAmount, 2),
                    'to_amount' => ($tier['to_amount'] ?? null) === null || ($tier['to_amount'] ?? '') === ''
                        ? null
                        : round((float) $tier['to_amount'], 2),
                    'fixed_fee' => round((float) ($tier['fixed_fee'] ?? 0), 8),
                    'percentage_fee' => round((float) ($tier['percentage_fee'] ?? 0), 4),
                ];
            })
            ->filter()
            ->sortBy('from_amount')
            ->values()
            ->all();

        if ($tiers === []) {
            throw ValidationException::withMessages([
                'tiers' => 'Informe pelo menos uma faixa de valor para usar o modelo por faixa.',
            ]);
        }

        foreach ($tiers as $index => $tier) {
            if ($tier['to_amount'] !== null && $tier['to_amount'] < $tier['from_amount']) {
                throw ValidationException::withMessages([
                    "tiers.{$index}.to_amount" => 'O valor final da faixa deve ser maior ou igual ao valor inicial.',
                ]);
            }
        }

        return [
            'pricing_model' => 'tiered',
            'tiers' => $tiers,
        ];
    }

    private function audit(
        Request $request,
        PlatformFeeRule $rule,
        string $action,
        ?array $oldValues,
        ?array $newValues,
        ?string $reason = null
    ): void {
        PlatformFeeRuleAudit::create([
            'platform_fee_rule_id' => $rule->id,
            'admin_id' => Auth::guard('admin')->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason ?? $request->input('reason'),
            'ip_address' => $request->ip(),
        ]);
    }

    private function storedResponse(Request $request, PlatformFeeRule $rule, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'rule_id' => $rule->id], 201);
        }

        return back()->withNotify([['success', $message]]);
    }
}
