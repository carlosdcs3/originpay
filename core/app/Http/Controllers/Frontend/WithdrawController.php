<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\NotifyErrorException;
use App\Http\Controllers\Controller;
use App\Models\WithdrawAccount;
use App\Models\WithdrawMethod;
use App\Services\Payment\WithdrawalRequestGuardService;
use App\Services\TransactionPasswordService;
use Exception;
use Illuminate\Http\Request;
use Payment;

class WithdrawController extends Controller
{
    public function create()
    {
        $pixMethod = WithdrawMethod::where('name', 'like', '%PIX%')
            ->orWhere('name', 'like', '%Pix%')
            ->first();

        $pixKeys = auth()->user()->pixKeys()->orderBy('is_primary', 'desc')->get();

        return view('frontend.user.withdraw.create', compact('pixMethod', 'pixKeys'));
    }

    /**
     * @throws Exception
     */
    public function store(Request $request, WithdrawalRequestGuardService $withdrawalRequestGuard)
    {
        $validated = $request->validate([
            'pix_key_id' => 'required|exists:pix_keys,id',
            'amount' => 'required|numeric|min:1',
            'transaction_password' => 'nullable|string|size:4|regex:/^\d{4}$/',
        ]);

        $user = $request->user();
        $tpService = app(TransactionPasswordService::class);
        
        if ($tpService->isLockedOut($user)) {
            throw new \App\Exceptions\NotifyErrorException('Muitas tentativas incorretas. Tente novamente em alguns minutos.');
        }

        if (! $tpService->verifyRequest($request, $user)) {
            throw new \App\Exceptions\NotifyErrorException('Senha transacional incorreta. Verifique e tente novamente.');
        }

        $withdrawalRequestGuard->ensureUserCanRequest($user);

        $pixKey = auth()->user()->pixKeys()->findOrFail($validated['pix_key_id']);

        $pixMethod = WithdrawMethod::where('name', 'like', '%PIX%')
            ->orWhere('name', 'like', '%Pix%')
            ->first();

        if (! $pixMethod) {
            throw new NotifyErrorException('O método de saque PIX não está disponível no momento.');
        }

        $wallet = auth()->user()->wallets()->where('balance', '>=', 0)->first();
        if (! $wallet) {
            throw new NotifyErrorException('Nenhuma carteira disponível foi encontrada para realizar o saque.');
        }

        $amount = (float) $validated['amount'];

        if (! $pixMethod->isWithinLimits($amount)) {
            throw new NotifyErrorException(sprintf(
                'O valor do saque deve estar entre %s e %s.',
                $this->formatCurrency((float) $pixMethod->min_withdraw),
                $this->formatCurrency((float) $pixMethod->max_withdraw)
            ));
        }

        $account = WithdrawAccount::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'withdraw_method_id' => $pixMethod->id,
                'name' => $pixKey->pix_key,
            ],
            [
                'credentials' => [
                    [
                        'name' => 'pix_key',
                        'type' => 'text',
                        'value' => $pixKey->pix_key,
                    ],
                    [
                        'name' => 'key_type',
                        'type' => 'text',
                        'value' => $pixKey->key_type,
                    ],
                ],
            ]
        );

        Payment::withdrawMoney($account, $wallet, $amount);

        notifyEvs('success', __('Withdrawal Requested and will be processed shortly.'));

        return redirect()->route('user.transaction.index');
    }

    public function credentialsFields($method_id)
    {
        $method = WithdrawMethod::find($method_id);
        $view = view('frontend.user.withdraw.partials.credentials_fields', compact('method'));

        return response()->json(['html' => $view->render(), 'method_name' => $method->name]);
    }

    private function formatCurrency(float $amount): string
    {
        return siteCurrency('symbol').' '.number_format($amount, 2, ',', '.');
    }
}
