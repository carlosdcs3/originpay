<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupportInboxController extends Controller
{
    /**
     * Inbox Geral
     */
    public function index()
    {
        return view('backend.support.inbox');
    }

    /**
     * Conversas Não Respondidas
     */
    public function unread()
    {
        return view('backend.support.unread');
    }

    /**
     * Em Andamento
     */
    public function active()
    {
        return view('backend.support.active');
    }

    /**
     * Resolvidas
     */
    public function resolved()
    {
        return view('backend.support.resolved');
    }

    /**
     * Base de Conhecimento
     */
    public function knowledgeBase()
    {
        return view('backend.support.knowledge_base');
    }

    /**
     * Macros
     */
    public function macros()
    {
        return view('backend.support.macros');
    }

    /**
     * Métricas
     */
    public function metrics()
    {
        return view('backend.support.metrics');
    }
}
