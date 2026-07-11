<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConnectContact;
use App\Models\ConnectSegment;
use App\Models\ConnectTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ConnectDashboardController extends Controller
{
    public function index()
    {
        $merchantId = Auth::id();

        $contactsCount = ConnectContact::where('merchant_id', $merchantId)->count();
        $segmentsCount = ConnectSegment::where('merchant_id', $merchantId)->count();
        $templatesCount = ConnectTemplate::where('merchant_id', $merchantId)->count();
        
        $campaignsCount = 0;
        if (DB::getSchemaBuilder()->hasTable('connect_campaigns')) {
            $campaignsCount = DB::table('connect_campaigns')->where('merchant_id', $merchantId)->whereNull('deleted_at')->count();
        }

        $hasCampaigns = $campaignsCount > 0;

        // Logic for Next Step
        $nextStep = 'contacts';
        if ($contactsCount > 0 && $segmentsCount == 0) $nextStep = 'segments';
        elseif ($contactsCount > 0 && $segmentsCount > 0 && $templatesCount == 0) $nextStep = 'templates';
        elseif ($contactsCount > 0 && $segmentsCount > 0 && $templatesCount > 0 && $campaignsCount == 0) $nextStep = 'campaigns';
        elseif ($hasCampaigns) $nextStep = 'done';

        return view('frontend.merchant.connect.index', compact(
            'hasCampaigns',
            'contactsCount',
            'segmentsCount',
            'templatesCount',
            'campaignsCount',
            'nextStep'
        ));
    }
}
