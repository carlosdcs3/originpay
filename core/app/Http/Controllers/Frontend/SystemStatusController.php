<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

class SystemStatusController extends Controller
{
    public function index()
    {
        $services = [
            [
                'name' => 'API Principal (V1)',
                'status' => 'operational',
                'uptime' => '99.99%',
            ],
            [
                'name' => 'Webhooks Dispatcher',
                'status' => 'operational',
                'uptime' => '99.95%',
            ],
            [
                'name' => 'Motor PIX',
                'status' => 'operational',
                'uptime' => '100%',
            ],
            [
                'name' => 'Motor Cartão',
                'status' => 'operational',
                'uptime' => '99.90%',
            ],
            [
                'name' => 'Efí Banking API',
                'status' => 'operational',
                'uptime' => '99.85%',
            ],
            [
                'name' => 'Ambiente Sandbox',
                'status' => 'operational',
                'uptime' => '100%',
            ]
        ];

        return view('frontend.system_status', compact('services'));
    }
}
