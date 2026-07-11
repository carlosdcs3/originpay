<?php
namespace App\Http\Controllers\Merchant\Connect;

use App\Http\Controllers\Controller;
use App\Services\Connect\Operations\OperationsService;
use App\Models\Connect\ConnectCampaignDlq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationsDlqController extends Controller
{
    public function index()
    {
        $dlqs = ConnectCampaignDlq::where('merchant_id', Auth::id())->paginate(50);
        return view('frontend.merchant.connect.operations.dlq', compact('dlqs'));
    }

    public function reprocess(Request $request, $id, OperationsService $ops)
    {
        $ops->dlq->recoverRecipient($id, Auth::id());
        return back()->with('success', 'Destinatário reinjetado no pipeline principal com sucesso.');
    }
    
    public function export(Request $request)
    {
        $merchantId = Auth::id();
        
        $response = new StreamedResponse(function() use ($merchantId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['UUID', 'Canal', 'Erro', 'Data']);

            ConnectCampaignDlq::where('merchant_id', $merchantId)
                ->cursor() // Uses Generator to save RAM
                ->each(function ($row) use ($handle) {
                    fputcsv($handle, [$row->uuid, $row->channel, $row->last_error, $row->created_at]);
                });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="dlq_export.csv"');
        
        return $response;
    }
    
    public function create()
    {
        return view("frontend.merchant.connect.operationsdlq"); // Defaulting to the index view for now as stub
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Criado com sucesso');
    }

    public function show($id)
    {
        return view("frontend.merchant.connect.operationsdlq");
    }

    public function edit($id)
    {
        return view("frontend.merchant.connect.operationsdlq");
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