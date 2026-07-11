<?php

namespace App\Services\Gateway;

use App\Models\PaymentGateway;
use Illuminate\Http\Request;

class GatewayCredentialManagerService
{
    /**
     * Build dynamic validation rules based on provider schema definition.
     */
    public function buildDynamicValidationRules(bool $isCustomOrLegacy, mixed $definition): array
    {
        $rules = [
            'is_sandbox' => 'boolean',
            'status' => 'boolean',
        ];

        if (! $isCustomOrLegacy) {
            foreach ($definition->credentials as $key => $schema) {
                if (isset($schema['rules'])) {
                    $rules["credentials.{$key}"] = $schema['rules'];
                }
            }
        } else {
            $rules['credential_keys'] = 'nullable|array';
            $rules['credential_values'] = 'nullable|array';
        }

        return $rules;
    }

    /**
     * Process credentials and file uploads based on provider schema.
     */
    public function processCredentialsAndUploads(Request $request, PaymentGateway $gateway, mixed $definition, bool $isCustomOrLegacy): array
    {
        $currentCredentials = is_array($gateway->credentials) ? $gateway->credentials : [];
        $newCredentials = [];

        if ($isCustomOrLegacy) {
            $keys = $request->input('credential_keys', []);
            $values = $request->input('credential_values', []);

            foreach ($keys as $index => $key) {
                $key = trim($key);
                if (empty($key)) {
                    continue;
                }

                $val = trim($values[$index] ?? '');

                if (empty($val) && isset($currentCredentials[$key])) {
                    $newCredentials[$key] = $currentCredentials[$key];
                } else {
                    $newCredentials[$key] = $val;
                }
            }

            return $newCredentials;
        }

        $inputCredentials = $request->input('credentials', []);

        foreach ($definition->credentials as $key => $schema) {
            // Tratamento para arquivos seguros (Certificados)
            if (($schema['input'] ?? '') === 'file') {
                if ($request->hasFile("credentials.{$key}")) {
                    $file = $request->file("credentials.{$key}");
                    $filename = $gateway->code.'_'.uniqid().'.'.$file->getClientOriginalExtension();

                    // Salvar fora da public_path por segurança
                    $path = $file->storeAs('gateways/certificates', $filename, 'local');
                    $newCredentials[$key] = $path;
                } else {
                    // Preservar caminho se não subiu arquivo novo
                    $newCredentials[$key] = $currentCredentials[$key] ?? '';
                }

                continue;
            }

            $val = $inputCredentials[$key] ?? '';

            // Tratamento de passwords (Bypass se vazio)
            $isSecret = ($schema['credential_type'] ?? '') === 'secret' || ($schema['input'] ?? '') === 'password';
            if ($isSecret && empty($val) && isset($currentCredentials[$key])) {
                $newCredentials[$key] = $currentCredentials[$key];
            } else {
                $newCredentials[$key] = trim($val);
            }
        }

        return $newCredentials;
    }
}
