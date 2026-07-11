<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Display the Alert Center.
     */
    public function index()
    {
        $alerts = class_exists(\App\Models\PlatformAlert::class) ? \App\Models\PlatformAlert::latest()->paginate(20) : collect([]);
        return view('backend.operations.alerts', compact('alerts'));
    }
}
