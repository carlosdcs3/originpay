<?php

namespace App\Http\Controllers\User\Developer;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\TransactionPasswordService;

class ApiKeyController extends Controller
{
    public function index()
    {
        $keys = ApiKey::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get();
        return view('frontend.user.developer.api-keys.index', compact('keys'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'environment' => 'required|in:test,live',
            'transaction_password' => 'nullable|string|size:4|regex:/^\d{4}$/',
        ]);

        $user = $request->user();
        $tpService = app(TransactionPasswordService::class);
        
        if ($tpService->isLockedOut($user)) {
            return redirect()->back()->with('error', 'Muitas tentativas incorretas. Tente novamente em alguns minutos.');
        }

        if (! $tpService->verifyRequest($request, $user)) {
            return redirect()->back()->with('error', 'Senha transacional incorreta. Verifique e tente novamente.');
        }

        $prefix = $request->environment === 'live' ? 'live' : 'test';
        
        $publicKey = 'pk_' . $prefix . '_' . Str::random(24);
        $secretKey = 'sk_' . $prefix . '_' . Str::random(24);

        $apiKey = ApiKey::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'environment' => $request->environment,
            'key_prefix' => $publicKey,
            'key_hash' => hash('sha256', $secretKey),
            'permissions' => ['*'],
        ]);

        return redirect()->route('user.developer.api-keys.index')->with([
            'success' => 'Chave gerada com sucesso!',
            'new_secret' => $secretKey,
            'new_key_id' => $apiKey->id
        ]);
    }

    public function revoke(Request $request, $id)
    {
        if (! app(TransactionPasswordService::class)->verifyRequest($request, $request->user())) {
            return redirect()->back()->with('error', 'Senha transacional incorreta. Verifique e tente novamente.');
        }

        $key = ApiKey::where('user_id', auth()->id())->findOrFail($id);
        $key->update(['status' => false]);

        return redirect()->route('user.developer.api-keys.index')->with('success', 'Chave revogada com sucesso!');
    }

    public function rotate(Request $request, $id)
    {
        if (! app(TransactionPasswordService::class)->verifyRequest($request, $request->user())) {
            return redirect()->back()->with('error', 'Senha transacional incorreta. Verifique e tente novamente.');
        }

        $key = ApiKey::where('user_id', auth()->id())->findOrFail($id);
        
        $prefix = $key->environment === 'live' ? 'live' : 'test';
        $newSecretKey = 'sk_' . $prefix . '_' . Str::random(24);

        $key->update([
            'key_hash' => hash('sha256', $newSecretKey),
            'rotated_at' => now()
        ]);

        return redirect()->route('user.developer.api-keys.index')->with([
            'success' => 'Secret rotacionada com sucesso!',
            'new_secret' => $newSecretKey,
            'new_key_id' => $key->id
        ]);
    }
}
