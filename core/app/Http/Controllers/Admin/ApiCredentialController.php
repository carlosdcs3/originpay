<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiCredential;
use App\Services\Auth\ApiKeyManagementService;

class ApiCredentialController extends Controller
{
    public function __construct(
        private readonly ApiKeyManagementService $apiKeyService
    ) {
    }

    public function index()
    {
        $credentials = ApiCredential::with('merchant')->paginate(20);
        // Note: View doesn't exist yet, we can return JSON for now since this is an API skeleton test
        return response()->json($credentials);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'environment' => 'required|in:sandbox,production',
        ]);

        $result = $this->apiKeyService->generateKeys(
            $request->merchant_id,
            $request->environment
        );

        return response()->json([
            'message' => 'API Key generated successfully. Save the secret key, it will not be shown again.',
            'data' => $result
        ], 201);
    }

    public function rotate(Request $request, $id)
    {
        $gracePeriod = $request->input('grace_period_minutes', 60);

        $result = $this->apiKeyService->rotateKey($id, $gracePeriod);

        if (!$result) {
            return response()->json(['message' => 'Credential not found or not active.'], 404);
        }

        return response()->json([
            'message' => 'API Key rotated successfully. Old key will expire after grace period.',
            'data' => $result
        ], 200);
    }

    public function revoke(Request $request, $id)
    {
        $revoked = $this->apiKeyService->revokeKey($id);

        if (!$revoked) {
            return response()->json(['message' => 'Credential not found.'], 404);
        }

        return response()->json(['message' => 'API Key revoked successfully.']);
    }
}
