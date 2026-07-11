<?php

namespace App\Http\Controllers\User\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SandboxController extends Controller
{
    public function index()
    {
        return view('frontend.user.developer.sandbox.index');
    }

    public function docs()
    {
        return view('frontend.user.developer.docs.index');
    }
}
