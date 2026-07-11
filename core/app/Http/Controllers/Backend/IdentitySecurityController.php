<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IdentitySecurityController extends Controller
{
    /**
     * Sessões Ativas (Gestão de Dispositivos e IP)
     */
    public function sessions()
    {
        $sessions = class_exists(\App\Models\UserLogin::class) ? \App\Models\UserLogin::latest()->paginate(20) : collect([]);
        return view('backend.identity.sessions', compact('sessions'));
    }

    /**
     * Logs de Login e Auditoria de Acesso
     */
    public function loginLogs()
    {
        $logs = class_exists(\App\Models\UserLogin::class) ? \App\Models\UserLogin::latest()->paginate(20) : collect([]);
        return view('backend.identity.login_logs', compact('logs'));
    }

    /**
     * Configuração de SSO & 2FA
     */
    public function sso2fa()
    {
        return view('backend.identity.sso_2fa');
    }

    /**
     * Dispositivos Conhecidos
     */
    public function devices()
    {
        return view('backend.identity.devices');
    }
}
