<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SystemAdminController extends Controller
{
    public function queues()
    {
        $pageTitle = 'Filas & Jobs';
        return view('backend.system.queues', compact('pageTitle'));
    }
}
