<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\PixKey;
use App\Services\TransactionPasswordService;
use Illuminate\Http\Request;

class PixKeyController extends Controller
{
    public function index()
    {
        $pixKeys = auth()->user()->pixKeys()->orderBy('is_primary', 'desc')->get();
        return view('frontend.user.setting.pix_keys-v2', compact('pixKeys'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'key_type' => 'required|in:cpf,cnpj,email,phone,random',
            'pix_key' => ['required', 'string', 'max:255', new \App\Rules\PixKeyRule($request->key_type)],
            'transaction_password' => 'nullable|string|size:4|regex:/^\d{4}$/',
        ]);

        $user = auth()->user();

        if (! app(TransactionPasswordService::class)->verifyRequest($request, $user)) {
            notifyEvs('error', 'Senha transacional incorreta. Verifique e tente novamente.');
            return back();
        }

        // Limit to 3 keys
        if ($user->pixKeys()->count() >= 3) {
            notifyEvs('error', __('Você atingiu o limite de 3 chaves PIX cadastradas.'));
            return back();
        }

        // Normalization
        $normalizedKey = $request->pix_key;
        if (in_array($request->key_type, ['cpf', 'cnpj'])) {
            $normalizedKey = preg_replace('/\D/', '', $normalizedKey);
        } elseif ($request->key_type === 'phone') {
            $normalizedKey = '+' . preg_replace('/\D/', '', $normalizedKey);
        } elseif ($request->key_type === 'email') {
            $normalizedKey = strtolower(trim($normalizedKey));
        } else {
            $normalizedKey = trim($normalizedKey);
        }

        // Prevent duplicate
        if ($user->pixKeys()->where('pix_key', $normalizedKey)->exists()) {
            notifyEvs('error', __('Esta chave já está cadastrada na sua conta.'));
            return back();
        }

        $isFirst = $user->pixKeys()->count() === 0;

        PixKey::create([
            'user_id' => $user->id,
            'key_type' => $request->key_type,
            'pix_key' => $normalizedKey,
            'is_primary' => $isFirst,
        ]);

        notifyEvs('success', __('Chave PIX adicionada com sucesso.'));
        return back();
    }

    public function setPrimary(Request $request, $id)
    {
        $user = auth()->user();

        if (! app(TransactionPasswordService::class)->verifyRequest($request, $user)) {
            notifyEvs('error', 'Senha transacional incorreta. Verifique e tente novamente.');
            return back();
        }

        $key = $user->pixKeys()->findOrFail($id);

        $user->pixKeys()->update(['is_primary' => false]);
        $key->update(['is_primary' => true]);

        notifyEvs('success', __('Chave principal definida com sucesso.'));
        return back();
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->user();

        if (! app(TransactionPasswordService::class)->verifyRequest($request, $user)) {
            notifyEvs('error', 'Senha transacional incorreta. Verifique e tente novamente.');
            return back();
        }

        $key = $user->pixKeys()->findOrFail($id);
        
        $wasPrimary = $key->is_primary;
        $key->delete();

        if ($wasPrimary) {
            $newPrimary = $user->pixKeys()->first();
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        notifyEvs('success', __('Chave PIX removida com sucesso.'));
        return back();
    }
}
