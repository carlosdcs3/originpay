<?php

namespace App\Services\Gateways\Adapters\Efi;

use App\Domain\Payments\GatewayRuntimeConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Exception;

class EfiHttpClient
{
    public function makeClient(GatewayRuntimeConfig $config, bool $requiresAuth = true, ?string $correlationId = null): PendingRequest
    {
        $certPath = $this->resolveCertificatePath($config->certificatePath);

        $baseUrl = rtrim($config->baseUrl ?? 'https://api-pix.gerencianet.com.br', '/');
        $correlationId = $correlationId ?? 'req_' . \Illuminate\Support\Str::random(16);

        $client = Http::baseUrl($baseUrl)
            ->timeout(15)
            ->retry(2, 500)
            ->withHeaders(['X-Correlation-ID' => $correlationId])
            ->withOptions([
                'cert' => $config->certificatePassword 
                    ? [$certPath, $config->certificatePassword] 
                    : $certPath,
            ]);

        if ($requiresAuth) {
            // To prevent circular dependency injected via constructor, we resolve it dynamically.
            $oauthService = app(EfiOAuthService::class);
            $token = $oauthService->getAccessToken($config);
            $client->withToken($token);
        }

        return $client;
    }

    private function resolveCertificatePath(?string $path): string
    {
        if (!$path) {
            throw new Exception("EFI certificate path is missing.");
        }

        // Check if path is absolute and within storage/app/private, or relative to it
        $baseDir = realpath(storage_path('app/private'));
        
        $possiblePaths = [
            $path,
            storage_path('app/private/' . $path)
        ];

        $realPath = false;
        foreach ($possiblePaths as $p) {
            $r = realpath($p);
            if ($r && file_exists($r)) {
                $realPath = $r;
                break;
            }
        }

        if (!$realPath) {
            throw new Exception("EFI certificate not found.");
        }

        if (!str_starts_with($realPath, $baseDir)) {
            throw new Exception("Invalid certificate path (Path traversal detected).");
        }

        return $realPath;
    }
}
