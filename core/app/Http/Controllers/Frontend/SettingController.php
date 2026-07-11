<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Services\Fees\PlatformFeeResolver;
use App\Services\TransactionPasswordService;
use App\Traits\FileManageTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class SettingController extends Controller
{
    use FileManageTrait;

    public function profile()
    {
        $user = auth()->user();

        return view('frontend.user.setting.profile-v2', compact('user'));
    }

    public function account(PlatformFeeResolver $feeResolver)
    {
        $user = auth()->user();
        $methods = [
            'pix' => ['label' => 'Pix', 'icon' => 'fas fa-qrcode', 'color' => '#7c3aed'],
            'card' => ['label' => 'Cartão', 'icon' => 'fas fa-credit-card', 'color' => '#3b82f6'],
            'boleto' => ['label' => 'Boleto', 'icon' => 'fas fa-barcode', 'color' => '#f59e0b'],
            'crypto' => ['label' => 'Crypto', 'icon' => 'fab fa-bitcoin', 'color' => '#10b981'],
        ];

        $appliedFees = collect($methods)->map(function (array $method, string $key) use ($feeResolver, $user) {
            $result = $feeResolver->resolve($user, $key, 100.00, 'BRL');
            $snapshot = $result->snapshot;

            return [
                'method' => $key,
                'label' => $method['label'],
                'icon' => $method['icon'],
                'color' => $method['color'],
                'source' => $result->source,
                'source_label' => $result->source === 'merchant'
                    ? 'Taxa negociada'
                    : ($result->source === 'fallback' ? 'Taxa padrão' : 'Taxa padrão'),
                'is_fallback' => $result->source === 'fallback',
                'fixed_fee' => $snapshot['fixed_fee'],
                'percentage_fee' => $snapshot['percentage_fee'],
                'minimum_fee' => $snapshot['minimum_fee'],
                'maximum_fee' => $snapshot['maximum_fee'],
                'settlement_delay_days' => $snapshot['settlement_delay_days'],
                'reserve_percentage' => $snapshot['reserve_percentage'],
            ];
        })->values();

        return view('frontend.user.setting.account-v2', compact('user', 'appliedFees'));
    }

    public function profileUpdate(Request $request)
    {
        $user = auth()->user();

        // validation rules
        $validate = $request->validate([
            'avatar'           => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'first_name'       => 'nullable',
            'last_name'        => 'nullable',
            'business_name'    => 'nullable',
            'business_address' => 'nullable',
            'username'         => 'required|unique:users,username,'.$user->id,
            'gender'           => ['required', new Enum(Gender::class)],
            'birthday'         => 'nullable|date',
            'phone'            => 'nullable',
            'country'          => 'nullable',
            'state'            => 'nullable',
            'city'             => 'nullable',
            'postal_code'      => 'nullable',
            'address'          => 'nullable',
            'email'            => 'required|unique:users,email,'.$user->id,
            'transaction_password' => 'nullable|string|size:4|regex:/^\d{4}$/',
        ]);

        if (! app(TransactionPasswordService::class)->verifyRequest($request, $user)) {
            notifyEvs('error', 'Senha transacional incorreta. Verifique e tente novamente.');

            return redirect()->back();
        }

        // if user uploaded a new avatar, update the avatar
        if ($request->hasFile('avatar')) {
            $validate['avatar'] = $this->uploadImage($request->file('avatar'), $user->avatar);
        }

        if ($user->email !== $validate['email']) {
            $validate['email_verified_at'] = null;
        }

        // update the user
        $user->update($validate);

        notifyEvs('success', 'Perfil atualizado com sucesso');

        // return the user back to the form with a success message
        return redirect()->back();
    }

    public function verifyEmail()
    {

        if (auth()->user()->hasVerifiedEmail()) {
            notifyEvs('warning', 'Seu endereço de e-mail já está verificado');

            return redirect()->intended(route('user.settings.profile'));
        }

        auth()->user()->sendEmailVerificationNotification();
        notifyEvs('success', 'Um novo link de verificação foi enviado para o seu e-mail');

        // return the user back to the form with an error message
        return redirect()->back();
    }

    public function changePassword()
    {
        return view('frontend.user.setting.change_password-v2');
    }

    public function passwordUpdate(Request $request)
    {
        $user     = auth()->user();
        $validate = $request->validate([
            'old_password' => 'required',
            'password'     => 'required|confirmed',
            'transaction_password' => 'nullable|string|size:4|regex:/^\d{4}$/',
        ]);

        if (! app(TransactionPasswordService::class)->verifyRequest($request, $user)) {
            notifyEvs('error', 'Senha transacional incorreta. Verifique e tente novamente.');

            return redirect()->back();
        }

        if (! password_verify($validate['old_password'], $user->password)) {
            notifyEvs('warning', 'Senha atual incorreta');

            return redirect()->back();
        }
        $user->password = bcrypt($validate['password']);
        $user->save();
        app(TransactionPasswordService::class)->forgetRecentVerification();
        notifyEvs('success', 'Senha atualizada com sucesso');

        return redirect()->back();
    }
}
