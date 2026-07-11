<?php

namespace App\Http\Controllers\Backend;

use App\Enums\MethodType;
use App\Gateway\Providers\Registry\ProviderRegistry;
use App\Models\PaymentGateway;
use App\Services\Gateway\GatewayCredentialManagerService;
use App\Services\Gateway\GatewayScaffoldService;
use App\Traits\FileManageTrait;
use Illuminate\Http\Request;

class PaymentGatewayController extends BaseController
{
    use FileManageTrait;

    public function __construct(
        protected GatewayScaffoldService $scaffoldService,
        protected GatewayCredentialManagerService $credentialService
    ) {}

    public static function permissions(): array
    {
        return [];
    }

    public function index()
    {
        $paymentGateways = PaymentGateway::paginate(10);

        return view('backend.payment_gateway.index', compact('paymentGateways'));
    }

    public function edit($id)
    {
        $paymentGateway = PaymentGateway::getById($id);

        return view('backend.payment_gateway.edit', compact('paymentGateway'))->render();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string',
        ]);

        $providerCode = $validated['provider'];

        try {
            $provider = ProviderRegistry::getProvider($providerCode);
            $definition = $provider->definition();
        } catch (\InvalidArgumentException $e) {
            notifyEvs('error', __('Provider inválido selecionado.'));

            return redirect()->back();
        }

        // Cuidando do nome para evitar colisão caso o admin crie múltiplos da mesma integração
        $count = PaymentGateway::where('code', $definition->code)->count();
        $finalCode = $count > 0 ? $definition->code.'_'.($count + 1) : $definition->code;
        $finalName = $count > 0 ? $definition->name.' '.($count + 1) : $definition->name;

        $gateway = new PaymentGateway;
        $gateway->provider = $definition->code;
        $gateway->adapter = $definition->adapter;
        $gateway->name = $finalName;
        $gateway->code = $finalCode;
        $gateway->logo = $definition->logo;
        $gateway->currencies = 'BRL'; // Default inicial
        $gateway->status = false; // Começa desativado para ele configurar
        $gateway->is_sandbox = true; // Sandbox by default
        $gateway->supports_pix = $definition->supports_pix;
        $gateway->supports_boleto = $definition->supports_boleto;
        $gateway->supports_card = $definition->supports_card;
        $gateway->supports_crypto = $definition->supports_crypto;
        $gateway->is_withdraw = $definition->is_withdraw;

        $gateway->credentials = $this->scaffoldService->resolveInitialCredentials($definition);
        $gateway->save();

        $this->scaffoldService->scaffoldAutomaticMethods($gateway, $definition);

        notifyEvs('success', __('Gateway criado com sucesso. Configure as credenciais.'));

        return redirect()->route('admin.payment.gateway.settings', ['id' => $gateway->id, 'tab' => 'credentials']);
    }

    public function update($id, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'status' => 'boolean',
            'is_sandbox' => 'boolean',
            'supports_pix' => 'boolean',
            'supports_boleto' => 'boolean',
            'supports_card' => 'boolean',
            'supports_crypto' => 'boolean',
            'is_withdraw' => 'boolean',
        ]);

        $paymentGateway = PaymentGateway::with(['depositMethods', 'withdrawMethods'])->findOrFail($id);

        $validated['status'] = $request->boolean('status');
        $validated['is_sandbox'] = $request->boolean('is_sandbox');
        $validated['supports_pix'] = $request->boolean('supports_pix');
        $validated['supports_boleto'] = $request->boolean('supports_boleto');
        $validated['supports_card'] = $request->boolean('supports_card');
        $validated['supports_crypto'] = $request->boolean('supports_crypto');
        $validated['is_withdraw'] = $request->boolean('is_withdraw');

        if (! $validated['status']) {
            $paymentGateway->depositMethods()->update(['status' => false]);
            $paymentGateway->withdrawMethods()->update(['status' => false]);
        }

        $paymentGateway->update($validated);

        notifyEvs('success', __('Payment Gateway Updated Successfully'));

        return redirect()->back();
    }

    public function gatewayCurrency($gateway_id)
    {
        $paymentGateway = PaymentGateway::getById($gateway_id);
        $supportedCurrencies = $paymentGateway->currencies;

        return [
            'view' => view('backend.payment_gateway.partial._currencies_list', compact('supportedCurrencies'))->render(),
        ];
    }

    // ==========================================
    // UX Enterprise Settings
    // ==========================================

    public function settings($id)
    {
        $gateway = PaymentGateway::with(['depositMethods', 'withdrawMethods'])->findOrFail($id);

        $pixCharge = $gateway->depositMethods()->where('type', MethodType::AUTOMATIC)->first();
        $pixWithdraw = $gateway->withdrawMethods()->where('type', MethodType::AUTOMATIC)->first();

        $routeName = request()->route()->getName();
        $parts = explode('.', $routeName);
        $activeTab = end($parts);

        if ($activeTab == 'settings') {
            $activeTab = 'overview';
        }

        // Try to load Provider Schema
        try {
            $providerInstance = ProviderRegistry::getProvider($gateway->provider);
            $definition = $providerInstance->definition();
        } catch (\Exception $e) {
            $definition = null;
        }

        return view('backend.payment_gateway.settings', compact('gateway', 'pixCharge', 'pixWithdraw', 'activeTab', 'definition'));
    }

    public function updateCredentials($id, Request $request)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $providerCode = $gateway->provider;

        try {
            $providerInstance = ProviderRegistry::getProvider($providerCode);
            $definition = $providerInstance->definition();
        } catch (\Exception $e) {
            $definition = null;
        }

        $isCustomOrLegacy = (! $definition || empty($definition->credentials) || ! is_array(reset($definition->credentials)));

        $rules = $this->credentialService->buildDynamicValidationRules($isCustomOrLegacy, $definition);
        $validated = $request->validate($rules);

        $newCredentials = $this->credentialService->processCredentialsAndUploads($request, $gateway, $definition, $isCustomOrLegacy);

        $gateway->update([
            'credentials' => $newCredentials,
            'is_sandbox' => $request->boolean('is_sandbox'),
            'status' => $request->boolean('status'),
        ]);

        \Log::info("Admin updated credentials for Gateway ID: {$gateway->id} - {$gateway->name} (Provider: {$gateway->provider})");

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Credenciais salvas com sucesso!'),
            ]);
        }

        notifyEvs('success', __('Credentials updated successfully.'));

        return redirect()->back();
    }

    public function storeDepositMethod($id, Request $request)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'currency' => 'required|string|max:10',
            'min_deposit' => 'nullable|numeric|min:0',
            'max_deposit' => 'nullable|numeric|min:0',
            'charge' => 'nullable|numeric|min:0',
            'charge_type' => 'required|in:fixed,percent',
            'merchant_charge' => 'nullable|numeric|min:0',
            'merchant_charge_type' => 'required|in:fixed,percent',
            'status' => 'boolean',
        ]);

        $dMethod = new DepositMethod;
        $dMethod->payment_gateway_id = $gateway->id;
        $dMethod->name = $validated['name'];
        $dMethod->currency = $validated['currency'];
        $dMethod->min_deposit = $validated['min_deposit'] ?? 0;
        $dMethod->max_deposit = $validated['max_deposit'] ?? 0;
        $dMethod->charge = $validated['charge'] ?? 0;
        $dMethod->charge_type = $validated['charge_type'];
        $dMethod->merchant_charge = $validated['merchant_charge'] ?? 0;
        $dMethod->merchant_charge_type = $validated['merchant_charge_type'];
        $dMethod->status = $request->boolean('status');
        $dMethod->save();

        \Log::info("Admin added deposit method {$dMethod->name} to Gateway ID: {$gateway->id}");

        notifyEvs('success', __('Método de cobrança criado com sucesso.'));

        return redirect()->back();
    }

    public function storeWithdrawMethod($id, Request $request)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'currency' => 'required|string|max:10',
            'min_limit' => 'nullable|numeric|min:0',
            'max_limit' => 'nullable|numeric|min:0',
            'charge' => 'nullable|numeric|min:0',
            'charge_type' => 'required|in:fixed,percent',
            'merchant_charge' => 'nullable|numeric|min:0',
            'merchant_charge_type' => 'required|in:fixed,percent',
            'status' => 'boolean',
            'field_labels' => 'nullable|array',
            'field_keys' => 'nullable|array',
            'field_types' => 'nullable|array',
            'field_placeholders' => 'nullable|array',
            'field_required' => 'nullable|array',
        ]);

        // Process fields builder
        $userFields = [];
        $labels = $request->input('field_labels', []);
        $keys = $request->input('field_keys', []);
        $types = $request->input('field_types', []);
        $placeholders = $request->input('field_placeholders', []);
        $requireds = $request->input('field_required', []);

        foreach ($labels as $index => $label) {
            $key = $keys[$index] ?? '';
            if (empty($key)) {
                continue;
            }

            $userFields[] = [
                'label' => $label,
                'key' => $key,
                'type' => $types[$index] ?? 'text',
                'placeholder' => $placeholders[$index] ?? '',
                'required' => (bool) ($requireds[$index] ?? false),
            ];
        }

        $wMethod = new WithdrawMethod;
        $wMethod->payment_gateway_id = $gateway->id;
        $wMethod->name = $validated['name'];
        $wMethod->currency = $validated['currency'];
        $wMethod->min_limit = $validated['min_limit'] ?? 0;
        $wMethod->max_limit = $validated['max_limit'] ?? 0;
        $wMethod->charge = $validated['charge'] ?? 0;
        $wMethod->charge_type = $validated['charge_type'];
        $wMethod->merchant_charge = $validated['merchant_charge'] ?? 0;
        $wMethod->merchant_charge_type = $validated['merchant_charge_type'];
        $wMethod->status = $request->boolean('status');
        $wMethod->fields = $userFields; // Salva o dynamic field builder
        $wMethod->save();

        \Log::info("Admin added withdraw method {$wMethod->name} to Gateway ID: {$gateway->id}");

        notifyEvs('success', __('Método de saque criado com sucesso.'));

        return redirect()->back();
    }

    public function updatePixCharge($id, Request $request)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $validated = $request->validate([
            'status' => 'boolean',
            'min_deposit' => 'nullable|numeric|min:0',
            'max_deposit' => 'nullable|numeric|min:0',
            'user_charge' => 'nullable|numeric|min:0',
            'user_charge_type' => 'nullable|string',
            'merchant_charge' => 'nullable|numeric|min:0',
            'merchant_charge_type' => 'nullable|string',
            'qr_expiration' => 'nullable|numeric',
            'auto_description' => 'nullable|string',
            'dynamic_qr' => 'nullable|boolean',
        ]);

        $pixCharge = $gateway->depositMethods()->where('type', MethodType::AUTOMATIC)->first();

        if (! $pixCharge) {
            $pixCharge = new \App\Models\DepositMethod;
            $pixCharge->payment_gateway_id = $gateway->id;
            $pixCharge->type = MethodType::AUTOMATIC;
            $pixCharge->name = 'Cobrança PIX';
            $pixCharge->code = $gateway->code.'_pix';
            $pixCharge->icon = 'fa-brands fa-pix'; // Added
            $pixCharge->currency = 'BRL';
            $pixCharge->currency_symbol = 'R$';
            $pixCharge->min_limit = 1; // Added
            $pixCharge->max_limit = 10000; // Added
            $pixCharge->rate_type = 'fixed'; // Added
            $pixCharge->rate = 1; // Replaced conversion_rate
            $pixCharge->charge_type = 'fixed';
            $pixCharge->charge = 0;
            $pixCharge->status = false;
            $pixCharge->fields = []; // Added
            $pixCharge->notes = ''; // Added
            $pixCharge->save();
        }

        $pixCharge->status = $request->boolean('status');
        if (isset($validated['min_deposit'])) {
            $pixCharge->min_deposit = $validated['min_deposit'];
        }
        if (isset($validated['max_deposit'])) {
            $pixCharge->max_deposit = $validated['max_deposit'];
        }
        if (isset($validated['user_charge'])) {
            $pixCharge->user_charge = $validated['user_charge'];
        }
        if (isset($validated['user_charge_type'])) {
            $pixCharge->user_charge_type = $validated['user_charge_type'];
        }
        if (isset($validated['merchant_charge'])) {
            $pixCharge->merchant_charge = $validated['merchant_charge'];
        }
        if (isset($validated['merchant_charge_type'])) {
            $pixCharge->merchant_charge_type = $validated['merchant_charge_type'];
        }

        $pixCharge->save();

        $gateway->supports_pix = true;
        $gateway->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Configurações de Cobrança PIX salvas com sucesso!'),
            ]);
        }

        notifyEvs('success', __('PIX Charge configuration updated successfully.'));

        return redirect()->back();
    }

    public function updatePixWithdraw($id, Request $request)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $validated = $request->validate([
            'status' => 'boolean',
            'min_withdraw' => 'nullable|numeric|min:0',
            'max_withdraw' => 'nullable|numeric|min:0',
            'user_charge' => 'nullable|numeric|min:0',
            'user_charge_type' => 'nullable|string',
            'merchant_charge' => 'nullable|numeric|min:0',
            'merchant_charge_type' => 'nullable|string',
            'manual_approval_threshold' => 'nullable|numeric',
        ]);

        $pixWithdraw = $gateway->withdrawMethods()->where('type', MethodType::AUTOMATIC)->first();

        if (! $pixWithdraw) {
            $pixWithdraw = new \App\Models\WithdrawMethod;
            $pixWithdraw->payment_gateway_id = $gateway->id;
            $pixWithdraw->type = MethodType::AUTOMATIC;
            $pixWithdraw->name = 'Saque PIX';
            $pixWithdraw->code = $gateway->code.'_pix_withdraw';
            $pixWithdraw->icon = 'fa-brands fa-pix';
            $pixWithdraw->currency = 'BRL';
            $pixWithdraw->currency_symbol = 'R$';
            $pixWithdraw->min_limit = 1;
            $pixWithdraw->max_limit = 10000;
            $pixWithdraw->rate_type = 'fixed';
            $pixWithdraw->rate = 1;
            $pixWithdraw->charge_type = 'fixed';
            $pixWithdraw->charge = 0;
            $pixWithdraw->process_time_value = 0;
            $pixWithdraw->process_time_unit = 'minute';
        }

        $pixWithdraw->status = $request->boolean('status');
        if (isset($validated['min_withdraw'])) {
            $pixWithdraw->min_withdraw = $validated['min_withdraw'];
        }
        if (isset($validated['max_withdraw'])) {
            $pixWithdraw->max_withdraw = $validated['max_withdraw'];
        }
        if (isset($validated['user_charge'])) {
            $pixWithdraw->user_charge = $validated['user_charge'];
        }
        if (isset($validated['user_charge_type'])) {
            $pixWithdraw->user_charge_type = $validated['user_charge_type'];
        }
        if (isset($validated['merchant_charge'])) {
            $pixWithdraw->merchant_charge = $validated['merchant_charge'];
        }
        if (isset($validated['merchant_charge_type'])) {
            $pixWithdraw->merchant_charge_type = $validated['merchant_charge_type'];
        }

        // Process fields builder se enviado
        if ($request->has('field_labels')) {
            $userFields = [];
            $labels = $request->input('field_labels', []);
            $keys = $request->input('field_keys', []);
            $types = $request->input('field_types', []);
            $placeholders = $request->input('field_placeholders', []);
            $requireds = $request->input('field_required', []);

            foreach ($labels as $index => $label) {
                $key = $keys[$index] ?? '';
                if (empty($key)) {
                    continue;
                }

                $userFields[] = [
                    'label' => $label,
                    'key' => $key,
                    'type' => $types[$index] ?? 'text',
                    'placeholder' => $placeholders[$index] ?? '',
                    'required' => (bool) ($requireds[$index] ?? false),
                ];
            }
            $pixWithdraw->fields = $userFields;
        }

        $pixWithdraw->save();

        $gateway->is_withdraw = true;
        $gateway->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Configurações de Saque PIX salvas com sucesso!'),
            ]);
        }

        notifyEvs('success', __('PIX Withdraw configuration updated successfully.'));

        return redirect()->back();
    }

    public function updateTaxes($id, Request $request)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $validated = $request->validate([
            'deposit_min' => 'nullable|numeric|min:0',
            'deposit_max' => 'nullable|numeric|min:0',
            'deposit_charge' => 'nullable|numeric|min:0',
            'deposit_charge_type' => 'nullable|string',

            'withdraw_min' => 'nullable|numeric|min:0',
            'withdraw_max' => 'nullable|numeric|min:0',
            'withdraw_charge' => 'nullable|numeric|min:0',
            'withdraw_charge_type' => 'nullable|string',
        ]);

        $pixCharge = $gateway->depositMethods()->where('type', MethodType::AUTOMATIC)->first();
        if ($pixCharge) {
            $pixCharge->min_deposit = $validated['deposit_min'] ?? 0;
            $pixCharge->max_deposit = $validated['deposit_max'] ?? 0;
            $pixCharge->charge = $validated['deposit_charge'] ?? 0;
            $pixCharge->charge_type = $validated['deposit_charge_type'] ?? 'fixed';
            $pixCharge->save();
        }

        $pixWithdraw = $gateway->withdrawMethods()->where('type', MethodType::AUTOMATIC)->first();
        if ($pixWithdraw) {
            $pixWithdraw->min_limit = $validated['withdraw_min'] ?? 0;
            $pixWithdraw->max_limit = $validated['withdraw_max'] ?? 0;
            $pixWithdraw->charge = $validated['withdraw_charge'] ?? 0;
            $pixWithdraw->charge_type = $validated['withdraw_charge_type'] ?? 'fixed';
            $pixWithdraw->save();
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Taxas e Limites atualizados com sucesso!'),
            ]);
        }

        notifyEvs('success', __('Taxes and Limits updated successfully.'));

        return redirect()->back();
    }

    public function updateRouting($id, Request $request)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $validated = $request->validate([
            'priority' => 'required|integer',
        ]);

        $gateway->update(['priority' => $validated['priority']]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Configurações de roteamento salvas com sucesso!'),
            ]);
        }

        notifyEvs('success', __('Routing settings updated successfully.'));

        return redirect()->back();
    }
}
