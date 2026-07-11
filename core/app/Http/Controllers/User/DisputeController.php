<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\DisputeEvidenceItem;
use App\Services\DisputeEventService;
use App\Services\DisputeEvidenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisputeController extends Controller
{
    public function __construct(
        protected DisputeEventService $eventService,
        protected DisputeEvidenceService $evidenceService
    ) {}

    public function index(Request $request)
    {
        $pageTitle = 'Reembolsos e Disputas';
        $merchantId = Auth::id();

        // Merchant only sees their own disputes
        $disputes = Dispute::where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate KPIs
        $kpiOpen = Dispute::where('merchant_id', $merchantId)->whereNotIn('status', ['won', 'lost', 'canceled', 'closed'])->count();
        $kpiWaitingMe = Dispute::where('merchant_id', $merchantId)->whereIn('status', ['waiting_merchant_docs'])->count();
        $kpiRetained = Dispute::where('merchant_id', $merchantId)->whereNotIn('status', ['won', 'canceled', 'closed'])->sum('retained_amount_cents');
        $kpiClosed = Dispute::where('merchant_id', $merchantId)->whereIn('status', ['won', 'lost', 'canceled', 'closed'])->count();

        return view('frontend.user.disputes.index', compact('pageTitle', 'disputes', 'kpiOpen', 'kpiWaitingMe', 'kpiRetained', 'kpiClosed'));
    }

    public function show($uuid)
    {
        $dispute = Dispute::with(['evidenceItems', 'messages' => function ($q) {
                // Do not load internal notes or messages sent by 'internal'
                $q->where('is_internal', false)
                  ->where('sender_type', '!=', 'internal')
                  ->oldest();
            }])
            ->where('uuid', $uuid)
            ->where('merchant_id', \Illuminate\Support\Facades\Auth::id())
            ->first();

        if (!$dispute) {
            abort(404);
        }

        $this->authorize('viewAsMerchant', $dispute);

        // Load and filter events using whitelist
        $publicEventTypes = [
            'dispute.created',
            'document.requested',
            'document.received',
            'merchant.message_sent',
            'originpay.message_sent',
            'evidence.sent_to_gateway',
            'dispute.closed',
            'dispute.won',
            'dispute.lost',
            'dispute.canceled'
        ];

        // Load events matching whitelist
        $events = $dispute->events()->whereIn('event_type', $publicEventTypes)->latest()->get();
        // Strip sensitive metadata before passing to view to guarantee privacy
        $events->transform(function ($event) {
            $event->metadata = null;
            return $event;
        });

        $pageTitle = 'Portal de Disputas';

        return view('frontend.user.disputes.show', compact('pageTitle', 'dispute', 'events'));
    }

    public function sendMessage(Request $request, $uuid)
    {
        $request->validate([
            'message' => 'required|string|max:2000'
        ]);

        $dispute = Dispute::where('uuid', $uuid)
            ->where('merchant_id', \Illuminate\Support\Facades\Auth::id())
            ->firstOrFail();

        $this->authorize('interactAsMerchant', $dispute);

        $dispute->messages()->create([
            'sender_type' => 'merchant',
            'sender_id' => Auth::id(),
            'message' => $request->message,
            'is_internal' => false
        ]);

        $this->eventService->log(
            $dispute, 
            'merchant.message_sent', 
            'Mensagem do Lojista', 
            'Nova mensagem recebida no portal.', 
            [] // Empty metadata intentionally from the merchant side
        );

        // Fire Domain Event for decoupling
        event(new \App\Domain\Disputes\Events\MerchantReplied($dispute, $request->message));

        return back()->with('success', 'Mensagem enviada com sucesso.');
    }

    public function uploadEvidence(Request $request, $uuid, $evidenceId)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,png,jpg,jpeg'
        ]);

        $dispute = Dispute::where('uuid', $uuid)
            ->where('merchant_id', \Illuminate\Support\Facades\Auth::id())
            ->firstOrFail();

        $this->authorize('interactAsMerchant', $dispute);

        $evidence = $dispute->evidenceItems()->where('id', $evidenceId)->firstOrFail();

        $file = $request->file('file');
        $hash = hash_file('sha256', $file->path());
        $originalName = $file->getClientOriginalName();

        // Use Evidence Service (Mocked)
        $mockStorageId = $this->evidenceService->processUpload($file);

        // Update evidence status
        $evidence->update([
            'status' => 'received',
            // Normally we'd store path/hash, we can store in a temp column or just leave it for the real implementation
        ]);

        $this->eventService->log(
            $dispute, 
            'document.received', 
            'Documento Recebido', 
            "O lojista enviou o documento: {$evidence->label}. (Hash: {$hash})", 
            []
        );

        // Fire Domain Event for decoupling
        event(new \App\Domain\Disputes\Events\EvidenceUploaded($dispute, $evidence));

        return back()->with('success', 'Arquivo enviado com sucesso. Nossa equipe iniciará a análise.');
    }
}
