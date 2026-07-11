<?php

namespace App\Http\Controllers\User\Developer;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Http\Request;

class ApiLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiLog::where('user_id', auth()->id())->with('apiKey');

        if ($request->has('method') && $request->method != '') {
            $query->where('method', $request->method);
        }

        if ($request->has('status') && $request->status != '') {
            if ($request->status == 'success') {
                $query->whereBetween('status_code', [200, 299]);
            } elseif ($request->status == 'error') {
                $query->where('status_code', '>=', 400);
            }
        }

        if ($request->has('date') && $request->date != '') {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('frontend.user.developer.logs.index', compact('logs'));
    }

    public function show($id)
    {
        $log = ApiLog::where('user_id', auth()->id())->with('apiKey')->findOrFail($id);

        // Sanitize headers (remove Authorization/Secret)
        if (is_array($log->request_headers)) {
            $sanitizedHeaders = [];
            foreach ($log->request_headers as $key => $value) {
                if (strtolower($key) === 'authorization' || strtolower($key) === 'x-api-key' || strtolower($key) === 'secret') {
                    $sanitizedHeaders[$key] = '*** SANITIZED ***';
                } else {
                    $sanitizedHeaders[$key] = $value;
                }
            }
            $log->request_headers = $sanitizedHeaders;
        }

        return response()->json($log);
    }
}
