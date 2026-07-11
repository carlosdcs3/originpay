<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Enums\DisputeStatus;
use App\Services\DisputeWorkflowService;
use App\Services\DisputeEventService;
use App\Http\Requests\Backend\Finance\Disputes\SendMessageRequest;
use App\Http\Requests\Backend\Finance\Disputes\RequestDocumentRequest;
use App\Http\Requests\Backend\Finance\Disputes\CloseDisputeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DisputeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DisputeWorkflowService $workflowService,
        protected DisputeEventService $eventService
    ) {}

    public function index(Request $request)
    {
        $pageTitle = 'Central de Disputas';

        // Mock KPI data from database
        $kpis = [
            'open_count' => Dispute::whereNotIn('status', ['won', 'lost', 'closed', 'canceled'])->count(),
            'retained_cents' => Dispute::whereNotIn('status', ['won', 'lost', 'closed', 'canceled'])->sum('retained_amount_cents'),
            'won_count' => Dispute::where('status', 'won')->count(),
            'lost_count' => Dispute::where('status', 'lost')->count(),
            'avg_response_hours' => 24, // Mocked for now
            'waiting_merchant_count' => Dispute::where('status', 'waiting_merchant_docs')->count(),
        ];

        // Paginate disputes
        $disputes = Dispute::with('merchant')->orderBy('created_at', 'desc')->paginate(20);

        return view('backend.finance.disputes.index', compact('pageTitle', 'disputes', 'kpis'));
    }

    public function show($uuid)
    {
        $dispute = Dispute::with(['merchant', 'messages', 'evidenceItems', 'events' => fn($q) => $q->latest()])
            ->withCount(['messages', 'evidenceItems'])
            ->where('uuid', $uuid)
            ->first();

        if (!$dispute) {
            abort(404);
        }

        $this->authorize('view', $dispute);

        // Operational Lock via Cache
        $lockKey = "dispute_lock_{$dispute->id}";
        $currentLock = Cache::get($lockKey);
        $userId = Auth::id() ?? 1;

        if ($currentLock && $currentLock['user_id'] !== $userId) {
            session()->flash('locked_by', "Este caso está sendo analisado por " . ($currentLock['user_name'] ?? 'Outro Analista') . " desde " . $currentLock['time']);
        } else {
            Cache::put($lockKey, [
                'user_id' => $userId,
                'user_name' => Auth::user()->name ?? 'Analista',
                'time' => now()->format('H:i')
            ], now()->addMinutes(15));
        }

        $pageTitle = 'Detalhe da Disputa';

        return view('backend.finance.disputes.show', compact('pageTitle', 'dispute'));
    }

    public function sendMessage(SendMessageRequest $request, $uuid)
    {
        $dispute = Dispute::where('uuid', $uuid)->firstOrFail();
        $isInternal = $request->boolean('is_internal_note');

        $dispute->messages()->create([
            'sender_type' => $isInternal ? 'internal' : 'admin',
            'sender_id' => Auth::id() ?? 1, // fallback
            'message' => $request->message,
        ]);

        $this->eventService->log(
            $dispute, 
            $isInternal ? 'internal_note' : 'public_message', 
            $isInternal ? 'Nota Interna Adicionada' : 'Mensagem enviada', 
            $request->message
        );

        return back()->with('success', $isInternal ? 'Nota interna salva com sucesso.' : 'Mensagem enviada com sucesso.');
    }

    public function requestDocument(RequestDocumentRequest $request, $uuid)
    {
        $dispute = Dispute::where('uuid', $uuid)->firstOrFail();

        $dispute->evidenceItems()->create([
            'type' => $request->document_type,
            'label' => $request->label,
            'status' => 'pending',
            'required' => true,
        ]);

        $this->eventService->log($dispute, 'document.requested', 'Documento solicitado', 'Tipo: ' . $request->label);

        return back()->with('success', 'Solicitação de documento registrada.');
    }

    public function notifyMerchant(Request $request, $uuid)
    {
        $dispute = Dispute::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $dispute);

        $this->eventService->log($dispute, 'merchant.notified', 'Lojista notificado manualmente', 'Notificação enviada por e-mail (mockado).');

        return back()->with('success', 'Lojista notificado com sucesso.');
    }

    public function sendGateway(Request $request, $uuid)
    {
        $dispute = Dispute::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $dispute);

        $this->workflowService->transitionTo(
            $dispute, 
            DisputeStatus::GATEWAY_REVIEW, 
            'gateway.sent', 
            'Evidências enviadas ao gateway', 
            'Envio mockado. Nenhuma integração externa foi chamada.'
        );

        return back()->with('success', 'Evidências enviadas ao gateway.');
    }

    public function releaseRetention(Request $request, $uuid)
    {
        $dispute = Dispute::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $dispute);

        $this->eventService->log($dispute, 'retention.released', 'Liberação de retenção solicitada', 'Solicitação mockada. Nenhuma movimentação real de saldo foi executada no ledger.');

        return back()->with('success', 'Solicitação de liberao de saldo registrada (MOCK).');
    }

    public function closeDispute(CloseDisputeRequest $request, $uuid)
    {
        $dispute = Dispute::where('uuid', $uuid)->firstOrFail();
        
        $status = match($request->close_result) {
            'won' => DisputeStatus::WON,
            'lost' => DisputeStatus::LOST,
            'canceled' => DisputeStatus::CANCELED,
        };

        $this->workflowService->close($dispute, $status, $request->reason);

        return back()->with('success', 'Disputa encerrada com sucesso.');
    }
}
