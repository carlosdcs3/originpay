<?php

namespace App\Http\Controllers\Backend;

use App\Models\CommercialPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlatformFeeController extends BaseController
{
    public static function permissions(): array
    {
        return [
            'index'  => 'site-setting-view',
            'update' => 'site-setting-update',
        ];
    }

    private function getDefaultPayload()
    {
        return [
            'pix' => [
                'mode' => 'range',
                'universal_fee' => 0.00,
                'range_limit' => 10.00,
                'range_fixed_fee' => 0.35,
                'range_percentage_fee' => 2.00,
                'range_additional_fixed_fee' => 0.30,
            ],
            'boleto' => [
                'fixed_fee' => 0.00,
                'percentage_fee' => 0.00,
                'min_value' => 0.00
            ],
            'credit_card' => [
                'percentage_fee' => 0.00,
                'fixed_fee' => 0.00
            ]
        ];
    }

    public function index()
    {
        $pageTitle = 'Taxas da Plataforma (Gateway)';
        $activePolicy = CommercialPolicy::active()->first();
        $payload = $activePolicy ? $activePolicy->payload : $this->getDefaultPayload();

        $audits = CommercialPolicy::with('admin')->orderBy('created_at', 'desc')->paginate(10);

        return view('backend.settings.platform_fee', compact('pageTitle', 'payload', 'audits'));
    }

    private function saveMergedPolicy(Request $request, $type, $newData)
    {
        $activePolicy = CommercialPolicy::active()->first();
        $oldPayload = $activePolicy ? $activePolicy->payload : $this->getDefaultPayload();
        
        $newPayload = $oldPayload;
        $newPayload[$type] = $newData;

        $appliedAt = $request->apply_type === 'future' ? $request->applied_at : now();

        CommercialPolicy::create([
            'admin_id' => Auth::guard('admin')->id(),
            'payload' => $newPayload,
            'changes' => ['type' => $type, 'before' => $oldPayload[$type], 'after' => $newData],
            'reason' => $request->reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'applied_at' => $appliedAt,
        ]);
    }

    public function updatePix(Request $request)
    {
        $request->validate([
            'pix_mode' => 'required|in:universal,range',
            'universal_fee' => 'nullable|numeric|min:0',
            'range_limit' => 'nullable|numeric|min:0',
            'range_fixed_fee' => 'nullable|numeric|min:0',
            'range_percentage_fee' => 'nullable|numeric|min:0|max:100',
            'range_additional_fixed_fee' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:255',
            'apply_type' => 'required|in:immediate,future',
            'applied_at' => 'required_if:apply_type,future|date|after:now',
        ]);

        $pixData = [
            'mode' => $request->pix_mode,
            'universal_fee' => (float) $request->universal_fee,
            'range_limit' => (float) $request->range_limit,
            'range_fixed_fee' => (float) $request->range_fixed_fee,
            'range_percentage_fee' => (float) $request->range_percentage_fee,
            'range_additional_fixed_fee' => (float) $request->range_additional_fixed_fee,
        ];

        $this->saveMergedPolicy($request, 'pix', $pixData);

        return back()->withNotify([['success', 'Taxas PIX atualizadas com sucesso na política comercial']]);
    }

    public function updateBoleto(Request $request)
    {
        $request->validate([
            'fixed_fee' => 'required|numeric|min:0',
            'percentage_fee' => 'required|numeric|min:0|max:100',
            'min_value' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'apply_type' => 'required|in:immediate,future',
            'applied_at' => 'required_if:apply_type,future|date|after:now',
        ]);

        $boletoData = [
            'fixed_fee' => (float) $request->fixed_fee,
            'percentage_fee' => (float) $request->percentage_fee,
            'min_value' => (float) $request->min_value,
        ];

        $this->saveMergedPolicy($request, 'boleto', $boletoData);

        return back()->withNotify([['success', 'Taxas de Boleto atualizadas com sucesso na política comercial']]);
    }

    public function updateCard(Request $request)
    {
        $request->validate([
            'percentage_fee' => 'required|numeric|min:0|max:100',
            'fixed_fee' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'apply_type' => 'required|in:immediate,future',
            'applied_at' => 'required_if:apply_type,future|date|after:now',
        ]);

        $cardData = [
            'percentage_fee' => (float) $request->percentage_fee,
            'fixed_fee' => (float) $request->fixed_fee,
        ];

        $this->saveMergedPolicy($request, 'credit_card', $cardData);

        return back()->withNotify([['success', 'Taxas de Cartão atualizadas com sucesso na política comercial']]);
    }
}

