<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConnectAnalyticsController extends Controller
{
    public function index()
    {
        $viewName = strtolower(str_replace(['Connect', 'Controller'], '', 'ConnectAnalyticsController'));
        // Special case routing
        if ($viewName === 'dashboard') $viewName = 'index';
        return view("frontend.merchant.connect.{$viewName}");
    }
}
