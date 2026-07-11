<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PaymentOperation;
use App\Enums\RoutingStrategy;
use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\PaymentMethodRoute;
use Illuminate\Http\Request;

class GatewayManagerController extends Controller
{
    /**
     * Routing & Priority Configuration — Enterprise Operation View
     */
    public function routing()
    {
        return redirect()->route('admin.payment.gateway.index');
    }

    /**
     * Save routing configuration.
     */
    public function storeRouting(Request $request)
    {
        $request->validate([
            'routes'                          => 'required|array',
            'routes.*.primary_gateway_id'     => 'nullable|exists:payment_gateways,id',
            'routes.*.fallback_gateway_ids'   => 'nullable|array',
            'routes.*.fallback_gateway_ids.*' => 'exists:payment_gateways,id',
            'routes.*.routing_strategy'       => 'nullable|string',
            'routes.*.enabled'                => 'nullable|boolean',
        ]);

        foreach ($request->routes as $operationValue => $data) {
            // Validate operation enum value
            try {
                $operation = PaymentOperation::from($operationValue);
            } catch (\ValueError) {
                continue; // Skip unknown operations
            }

            $route = PaymentMethodRoute::firstOrNew(['payment_operation' => $operationValue]);

            // Always keep payment_method in sync for legacy compat
            $route->payment_method = $operation->paymentMethod()->value;

            // Double-check gateway compatibility server-side
            if (!empty($data['primary_gateway_id'])) {
                $gw = PaymentGateway::find($data['primary_gateway_id']);
                if (!$gw || !$gw->{$operation->supportFlag()}) {
                    $gwName = $gw?->name ?? "ID {$data['primary_gateway_id']}";
                    return back()->with('error', "O gateway {$gwName} não suporta a operação {$operation->label()}.");
                }
            }

            if (!empty($data['fallback_gateway_ids'])) {
                foreach ($data['fallback_gateway_ids'] as $fgid) {
                    $gw = PaymentGateway::find($fgid);
                    if (!$gw || !$gw->{$operation->supportFlag()}) {
                        $gwName = $gw?->name ?? "ID {$fgid}";
                        return back()->with('error', "O gateway fallback {$gwName} não suporta a operação {$operation->label()}.");
                    }
                }
            }

            // Validate strategy — only save valid enum values
            $strategyValue = $data['routing_strategy'] ?? 'manual';
            try {
                $strategy = RoutingStrategy::from($strategyValue);
            } catch (\ValueError) {
                $strategy = RoutingStrategy::MANUAL;
            }

            $route->primary_gateway_id   = $data['primary_gateway_id'] ?? null;
            $route->fallback_gateway_ids = array_values(array_filter(array_map('intval', $data['fallback_gateway_ids'] ?? [])));
            $route->routing_strategy     = $strategy->value;
            $route->enabled              = isset($data['enabled']) && $data['enabled'] == 1;
            $route->save();
        }

        return back()->with('success', 'Roteamento de pagamentos atualizado com sucesso.');
    }

    /**
     * Fallback Configuration
     */
    public function fallback()
    {
        return redirect()->route('admin.payment.gateway.index');
    }

    /**
     * Capabilities per Gateway
     */
    public function capabilities()
    {
        return redirect()->route('admin.payment.gateway.index');
    }

    /**
     * API Connectivity & Endpoints
     */
    public function connectivity()
    {
        return redirect()->route('admin.payment.gateway.index');
    }

    /**
     * Gateway Monitor (Overall)
     */
    public function monitor()
    {
        return redirect()->route('admin.payment.gateway.index');
    }
    
    /**
     * Gateway Individual Monitor
     */
    public function show($id)
    {
        return redirect()->route('admin.payment.gateway.overview', $id);
    }
}

