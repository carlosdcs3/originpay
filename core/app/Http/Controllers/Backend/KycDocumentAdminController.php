<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\FinancialComplianceAudit;

class KycDocumentAdminController extends Controller
{
    public function download(Request $request)
    {
        $path = $request->query('path');
        
        if (!$path) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($path) && !Storage::disk('public')->exists($path)) {
            abort(404, 'Document not found.');
        }

        // Audit Log
        FinancialComplianceAudit::create([
            'admin_id' => auth()->id(),
            'action' => 'kyc_document_viewed',
            'details' => 'Admin viewed KYC document',
            'ip_address' => request()->ip(),
            'metadata' => [
                'path_hash' => hash('sha256', $path) // Avoid logging raw path explicitly if sensitive
            ]
        ]);

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path);
        }

        return Storage::disk('public')->response($path);
    }
}
