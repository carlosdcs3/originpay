<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TransactionPasswordService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TransactionPasswordController extends Controller
{
    protected $tpService;

    public function __construct(TransactionPasswordService $tpService)
    {
        $this->tpService = $tpService;
    }

    /**
     * Store a new transaction password.
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_password' => ['required', 'string', 'size:4', 'regex:/^[0-9]+$/', 'confirmed'],
        ], [
            'transaction_password.size' => 'A senha transacional deve ter exatamente 4 dígitos.',
            'transaction_password.regex' => 'A senha transacional deve conter apenas números.',
            'transaction_password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $this->validateWeakPassword($request->transaction_password);

        $user = auth()->user();

        if ($this->tpService->hasPassword($user)) {
            return redirect()->back()->with('error', 'Você já possui uma senha transacional cadastrada.');
        }

        $this->tpService->createPassword($user, $request->transaction_password);

        return redirect()->back()->with('success', 'Senha transacional criada com sucesso.');
    }

    /**
     * Update an existing transaction password.
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'current_transaction_password' => 'required',
            'transaction_password' => ['required', 'string', 'size:4', 'regex:/^[0-9]+$/', 'confirmed', 'different:current_transaction_password'],
        ], [
            'transaction_password.size' => 'A nova senha deve ter exatamente 4 dígitos.',
            'transaction_password.regex' => 'A nova senha deve conter apenas números.',
            'transaction_password.confirmed' => 'A confirmação da nova senha não confere.',
            'transaction_password.different' => 'A nova senha não pode ser igual à anterior.',
        ]);

        $user = auth()->user();

        // Validate current login password
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->with('error', 'Senha de login atual incorreta.');
        }

        // Validate current transaction password
        if ($this->tpService->isLockedOut($user)) {
            return redirect()->back()->with('error', 'Muitas tentativas incorretas. Tente novamente em alguns minutos.');
        }

        if (!$this->tpService->validate($user, $request->current_transaction_password)) {
            return redirect()->back()->with('error', 'Senha transacional atual incorreta. Verifique e tente novamente.');
        }

        $this->validateWeakPassword($request->transaction_password);

        $this->tpService->changePassword($user, $request->transaction_password);

        return redirect()->back()->with('success', 'Senha transacional alterada com sucesso.');
    }

    /**
     * Helper to block weak passwords.
     */
    protected function validateWeakPassword($password)
    {
        $weakPasswords = ['0000', '1111', '2222', '3333', '4444', '5555', '6666', '7777', '8888', '9999', '1234', '4321', '1212', '1122'];
        
        if (in_array($password, $weakPasswords)) {
            throw ValidationException::withMessages([
                'transaction_password' => 'Esta senha é muito fraca. Escolha uma combinação mais segura.',
            ]);
        }
    }
}
