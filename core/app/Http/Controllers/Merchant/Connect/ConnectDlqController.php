<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConnectDlqController extends Controller
{
    public function index()
    {
        $viewName = strtolower(str_replace(['Connect', 'Controller'], '', 'ConnectDlqController'));
        // Special case routing
        if ($viewName === 'dashboard') $viewName = 'index';
        return view("frontend.merchant.connect.{$viewName}");
    }
}
