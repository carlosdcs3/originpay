<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use App\Services\Connect\CampaignService;
use App\Services\Connect\Campaign\CampaignDryRunService;
use App\Models\Connect\Campaign;
use App\Models\Connect\Template;
use App\Models\Connect\ConnectSegment;
use App\Support\Connect\Capabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    protected $service;

    public function __construct(CampaignService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $this->authorize(Capabilities::CAMPAIGN_READ, Campaign::class);
        $campaigns = Campaign::forMerchant(Auth::id())->with(['segment', 'template'])->orderBy('id', 'desc')->paginate(15);
        return view('frontend.merchant.connect.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $this->authorize(Capabilities::CAMPAIGN_WRITE, Campaign::class);
        $segments = ConnectSegment::forMerchant(Auth::id())->get();
        $templates = Template::forMerchant(Auth::id())->where('is_current', true)->where('status', 'published')->get();
        return view('frontend.merchant.connect.campaigns.create', compact('segments', 'templates'));
    }

    public function store(Request $request)
    {
        $this->authorize(Capabilities::CAMPAIGN_WRITE, Campaign::class);
        $validated = $request->validate([
            'channel' => 'required|in:email,whatsapp,sms',
            'name' => 'required|string|max:255',
            'segment_id' => 'required|exists:connect_segments,id',
            'template_id' => 'required|exists:connect_templates,id',
        ]);

        $this->service->createCampaign($validated, Auth::id(), Auth::id());
        return redirect()->route('merchant.connect.campaigns.index')->with('success', 'Campanha Criada em Draft.');
    }

    public function show($id)
    {
        $this->authorize(Capabilities::CAMPAIGN_READ, Campaign::class);
        $campaign = Campaign::forMerchant(Auth::id())->with(['segment', 'template'])->findOrFail($id);
        return view('frontend.merchant.connect.campaigns.show', compact('campaign'));
    }

    public function schedule(Request $request, $id)
    {
        $this->authorize(Capabilities::CAMPAIGN_EXECUTE, Campaign::class);
        $campaign = Campaign::forMerchant(Auth::id())->findOrFail($id);
        
        try {
            $this->service->scheduleCampaign($campaign, now(), Auth::id());
            return back()->with('success', 'Campanha Agendada com sucesso (Snapshot Realizado).');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function dryRun(Request $request, $id, CampaignDryRunService $dryRunService)
    {
        $this->authorize(Capabilities::CAMPAIGN_READ, Campaign::class);
        $campaign = Campaign::forMerchant(Auth::id())->findOrFail($id);
        
        try {
            $results = $dryRunService->run($campaign);
            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
