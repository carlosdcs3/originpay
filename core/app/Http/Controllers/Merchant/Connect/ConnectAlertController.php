<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConnectAlertController extends Controller
{
    public function index()
    {
        $viewName = strtolower(str_replace(['Connect', 'Controller'], '', 'ConnectAlertController'));
        // Special case routing
        if ($viewName === 'dashboard') $viewName = 'index';
        return view("frontend.merchant.connect.{$viewName}");
    }
    
    public function create()
    {
        return view("frontend.merchant.connect.alert"); // Defaulting to the index view for now as stub
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Criado com sucesso');
    }

    public function show($id)
    {
        return view("frontend.merchant.connect.alert");
    }

    public function edit($id)
    {
        return view("frontend.merchant.connect.alert");
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