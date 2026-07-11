<?php

namespace App\Services\Compliance;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\KycStatus;

class LgpdService
{
    /**
     * Encerra a conta e anonimiza todos os dados sensíveis do usuário.
     * Os registros financeiros (Ledger, Charges, etc) são mantidos íntegros 
     * por obrigação legal (Bacen).
     */
    public function anonymizeAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            $uuid = Str::uuid()->toString();
            $anonPrefix = 'ANON_';
            
            // 1. Remover Anexos (KYC)
            // Assumindo que KYC Documents estão em user->kyc_documents ou na tabela de kyc
            $this->deleteKycAttachments($user);
            
            // 2. Zerar e anonimizar colunas do User
            $user->name = $anonPrefix . substr($uuid, 0, 8);
            $user->email = $anonPrefix . $uuid . '@anonymized.local';
            $user->phone = $anonPrefix . rand(100000000, 999999999);
            $user->document = $anonPrefix . rand(10000000000, 99999999999);
            $user->password = bcrypt(Str::random(32)); // Reset password
            $user->status = 0; // Inativo
            $user->kyc_status = KycStatus::REJECTED->value;
            $user->api_key = null;
            $user->api_secret = null;
            $user->remember_token = null;
            
            // Limpar sessões ativas (se usar banco de dados)
            DB::table('sessions')->where('user_id', $user->id)->delete();
            
            // Tokens (Sanctum/Passport)
            $user->tokens()->delete();

            $user->save();

            // 3. Registrar auditoria do encerramento da conta
            // Podemos usar um log genérico ou a tabela audit_logs que será criada
            \Log::channel('compliance')->info("Account Anonymized (LGPD): User ID {$user->id}");
        });
    }

    protected function deleteKycAttachments(User $user): void
    {
        // Se houver uma relação KYC
        $kyc = $user->kyc()->first();
        if ($kyc) {
            if ($kyc->document_front && Storage::exists($kyc->document_front)) {
                Storage::delete($kyc->document_front);
                $kyc->document_front = null;
            }
            if ($kyc->document_back && Storage::exists($kyc->document_back)) {
                Storage::delete($kyc->document_back);
                $kyc->document_back = null;
            }
            if ($kyc->selfie && Storage::exists($kyc->selfie)) {
                Storage::delete($kyc->selfie);
                $kyc->selfie = null;
            }
            // Anonimizar dados extras na tabela kyc
            $kyc->full_name = 'ANONYMIZED';
            $kyc->birth_date = null;
            $kyc->mother_name = null;
            $kyc->address = null;
            $kyc->save();
        }
    }
}
