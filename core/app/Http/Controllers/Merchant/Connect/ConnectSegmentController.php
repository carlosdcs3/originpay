<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConnectSegment;
use Illuminate\Support\Facades\Auth;

class ConnectSegmentController extends Controller
{
    public function index(Request $request)
    {
        $merchantId = Auth::id();

        // 1. Calculate KPI Metrics
        $totalSegments = ConnectSegment::where('merchant_id', $merchantId)->count();
        $dynamicSegments = ConnectSegment::where('merchant_id', $merchantId)->where('is_dynamic', true)->count();
        $segmentedContacts = ConnectSegment::where('merchant_id', $merchantId)->sum('total_contacts');
        
        $lastUpdate = ConnectSegment::where('merchant_id', $merchantId)->max('updated_at');

        // 2. Query Segments
        $query = ConnectSegment::where('merchant_id', $merchantId);

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->filled('type')) {
            if ($request->type == 'dynamic') {
                $query->where('is_dynamic', true);
            } elseif ($request->type == 'static') {
                $query->where('is_dynamic', false);
            }
        }

        $segments = $query->latest('updated_at')->paginate(15)->withQueryString();

        return view('frontend.merchant.connect.segment', compact(
            'totalSegments',
            'dynamicSegments',
            'segmentedContacts',
            'lastUpdate',
            'segments'
        ));
    }
    
    public function create()
    {
        return view("frontend.merchant.connect.segments.create");
    }

    public function preview(Request $request)
    {
        $merchantId = Auth::id();
        $engine = new \App\Services\Connect\SegmentEngine();
        
        $payload = $request->input('payload', []);
        
        try {
            $query = $engine->buildQuery($merchantId, $payload);
            $total = $query->count();
            $contacts = $query->limit(10)->get();
            
            return response()->json([
                'success' => true,
                'total' => $total,
                'contacts' => $contacts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
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