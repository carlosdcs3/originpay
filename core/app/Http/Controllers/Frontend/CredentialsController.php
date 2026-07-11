<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\EnvironmentMode;
use App\Enums\MerchantStatus;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Services\TransactionPasswordService;
use Illuminate\Http\Request;

class CredentialsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Find the default wallet currency or fallback to 1
        $currencyId = $user->activeWallets()->first()->currency_id ?? 1;

        // Automatically create a merchant record for the user if they don't have one
        // so they can use the API integration instantly.
        $merchant = Merchant::firstOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => $user->first_name . ' ' . $user->last_name,
                'site_url' => 'https://seusite.com.br',
                'currency_id' => $currencyId,
                'status' => MerchantStatus::APPROVED, // Instantly approve
                'sandbox_enabled' => true,
                'current_mode' => EnvironmentMode::PRODUCTION,
            ]
        );

        // Just in case it was created pending previously, force approve it so they can see keys
        if ($merchant->status !== MerchantStatus::APPROVED) {
            $merchant->status = MerchantStatus::APPROVED;
            $merchant->save();
        }

        return view('frontend.user.credentials.index', compact('merchant'));
    }

    public function updateWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => 'required|url',
            'transaction_password' => 'nullable|string|size:4|regex:/^\d{4}$/',
        ]);

        if (! app(TransactionPasswordService::class)->verifyRequest($request, $request->user())) {
            notifyEvs('error', 'Senha transacional incorreta. Verifique e tente novamente.');

            return back();
        }

        $merchant = Merchant::where('user_id', auth()->id())->firstOrFail();
        $merchant->webhook_url = $request->webhook_url;
        $merchant->save();

        notifyEvs('success', __('URL de Webhook atualizada com sucesso!'));
        return back();
    }
}
