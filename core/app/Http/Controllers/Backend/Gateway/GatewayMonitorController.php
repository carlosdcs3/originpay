<?php

namespace App\Http\Controllers\Backend\Gateway;

use App\Http\Controllers\Controller;

class GatewayMonitorController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.payment.gateway.index');
    }
}
