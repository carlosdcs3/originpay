<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Exception;

class HealthCheckController extends Controller
{
    /**
     * Liveness probe: Checks if the application is up and running.
     * Fast and light, no external dependencies checked.
     */
    public function live()
    {
        return response()->json(['status' => 'UP']);
    }

    /**
     * Readiness probe: Checks if the application is ready to handle traffic.
     * Verifies critical dependencies like DB, Redis and Storage.
     * Protected by X-Monitor-Token header.
     */
    public function ready(Request $request)
    {
        $token = $request->header('X-Monitor-Token');
        $expectedToken = config('app.monitor_token', 'default-secret-token'); // fallback for safety, should be configured

        if ($token !== $expectedToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $status = 'UP';
        $checks = [];

        // 1. Check Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'OK';
        } catch (Exception $e) {
            $status = 'DOWN';
            $checks['database'] = 'ERROR';
        }

        // 2. Check Redis
        try {
            Redis::connection()->ping();
            $checks['redis'] = 'OK';
        } catch (Exception $e) {
            $status = 'DOWN';
            $checks['redis'] = 'ERROR';
        }

        // 3. Check Storage (local)
        try {
            $disk = Storage::disk('local');
            $testFile = 'health_check_test.txt';
            $disk->put($testFile, 'test');
            $disk->delete($testFile);
            $checks['storage'] = 'OK';
        } catch (Exception $e) {
            $status = 'DOWN';
            $checks['storage'] = 'ERROR';
        }

        $statusCode = $status === 'UP' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'checks' => $checks
        ], $statusCode);
    }
}
