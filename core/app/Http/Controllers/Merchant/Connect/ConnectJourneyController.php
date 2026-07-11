<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConnectJourneyController extends Controller
{
    public function index()
    {
        $viewName = strtolower(str_replace(['Connect', 'Controller'], '', 'ConnectJourneyController'));
        // Special case routing
        if ($viewName === 'dashboard') $viewName = 'index';
        return view("frontend.merchant.connect.{$viewName}");
    }
    
    public function create()
    {
        return view("frontend.merchant.connect.journey"); // Defaulting to the index view for now as stub
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Criado com sucesso');
    }

    public function show($id)
    {
        return view("frontend.merchant.connect.journey");
    }

    public function edit($id)
    {
        return view("frontend.merchant.connect.journey");
    }

    public function update(Request $request, $id)
    {
        return back()->with('success', 'Atualizado com sucesso');
    }

    public function destroy($id)
    {
        return back()->with('success', 'Removido com sucesso');
    }
}