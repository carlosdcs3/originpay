<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConnectTemplate;
use Illuminate\Support\Facades\Auth;

class ConnectTemplateController extends Controller
{
    public function index(Request $request)
    {
        $merchantId = Auth::id();

        // 1. Calculate KPI Metrics
        $totalTemplates = ConnectTemplate::where('merchant_id', $merchantId)->count();
        $emailTemplates = ConnectTemplate::where('merchant_id', $merchantId)->where('channel', 'email')->count();
        $whatsappTemplates = ConnectTemplate::where('merchant_id', $merchantId)->where('channel', 'whatsapp')->count();
        $publishedTemplates = ConnectTemplate::where('merchant_id', $merchantId)
            ->where(function($q) {
                $q->whereNotNull('published_at')->orWhere('status', 'published');
            })
            ->count();

        // 2. Query Templates
        $query = ConnectTemplate::where('merchant_id', $merchantId);

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        $templates = $query->latest('updated_at')->paginate(15)->withQueryString();

        return view('frontend.merchant.connect.template', compact(
            'totalTemplates',
            'emailTemplates',
            'whatsappTemplates',
            'publishedTemplates',
            'templates'
        ));
    }
    
    public function create()
    {
        return view("frontend.merchant.connect.coming_soon");
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Criado com sucesso');
    }

    public function show($id)
    {
        return view("frontend.merchant.connect.coming_soon");
    }

    public function edit($id)
    {
        return view("frontend.merchant.connect.coming_soon");
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