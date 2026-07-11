<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $pageTitle = 'Relatórios Gerais';
        return view('backend.reports.index', compact('pageTitle'));
    }
}
