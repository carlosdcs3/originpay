<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    /**
     * Feature Flags
     */
    public function featureFlags()
    {
        return view('backend.platform.feature_flags');
    }

    /**
     * Versionamento (Versões do App, Web, APIs)
     */
    public function versioning()
    {
        return view('backend.platform.versioning');
    }

    /**
     * Changelog
     */
    public function changelog()
    {
        return view('backend.platform.changelog');
    }
}
