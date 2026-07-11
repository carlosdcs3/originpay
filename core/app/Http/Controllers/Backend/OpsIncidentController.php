<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PlatformIncident;
use Illuminate\Http\Request;

class OpsIncidentController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Incidentes Críticos';

        $query = PlatformIncident::query()->orderBy('id', 'desc');

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('severity') && $request->severity != '') {
            $query->where('severity', $request->severity);
        }

        $incidents = $query->paginate(20);

        return view('backend.ops.incidents', compact('pageTitle', 'incidents'));
    }
}
