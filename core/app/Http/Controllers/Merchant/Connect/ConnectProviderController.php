<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConnectProviderController extends Controller
{
    public function index()
    {
        $viewName = strtolower(str_replace(['Connect', 'Controller'], '', 'ConnectProviderController'));
        // Special case routing
        if ($viewName === 'dashboard') $viewName = 'index';
        return view("frontend.merchant.connect.{$viewName}");
    }
    
    public function create()
    {
        return view("frontend.merchant.connect.provider"); // Defaulting to the index view for now as stub
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Criado com sucesso');
    }

    public function show($id)
    {
        return view("frontend.merchant.connect.provider");
    }

    public function edit($id)
    {
        return view("frontend.merchant.connect.provider");
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