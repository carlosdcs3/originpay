<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use App\Services\Connect\SegmentService;
use App\Models\Connect\ConnectSegment;
use App\Support\Connect\Capabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SegmentController extends Controller
{
    protected $segmentService;

    public function __construct(SegmentService $segmentService)
    {
        $this->segmentService = $segmentService;
    }

    public function index()
    {
        $this->authorize(Capabilities::SEGMENT_READ, ConnectSegment::class);
        $segments = ConnectSegment::forMerchant(Auth::id())->paginate(15);
        return view('frontend.merchant.connect.segments.index', compact('segments'));
    }

    public function create()
    {
        $this->authorize(Capabilities::SEGMENT_WRITE, ConnectSegment::class);
        return view('frontend.merchant.connect.segments.create');
    }

    public function store(Request $request)
    {
        $this->authorize(Capabilities::SEGMENT_WRITE, ConnectSegment::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rules' => 'required|string', // Taking raw JSON from UI
        ]);

        $this->segmentService->createSegment($validated, Auth::id());
        return redirect()->route('merchant.connect.segments.index')->with('success', 'Segmento criado.');
    }

    public function edit($id)
    {
        $this->authorize(Capabilities::SEGMENT_WRITE, ConnectSegment::class);
        $segment = ConnectSegment::forMerchant(Auth::id())->findOrFail($id);
        return view('frontend.merchant.connect.segments.edit', compact('segment'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize(Capabilities::SEGMENT_WRITE, ConnectSegment::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rules' => 'required|string',
        ]);

        $this->segmentService->updateSegment($id, $validated, Auth::id());
        return redirect()->route('merchant.connect.segments.index')->with('success', 'Segmento atualizado.');
    }
}
