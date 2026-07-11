<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SupportChat;
use App\Models\SupportChatAttachment;
use App\Models\SupportConversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Services\SupportChatService;

class SupportChatController extends Controller
{
    public function __construct(
        protected SupportChatService $chatService
    ) {}
    /**
     * Get the overall state of the support center for the user (unread count + conversations list).
     */
    public function state()
    {
        $userId = auth()->id();
        
        $conversations = SupportConversation::where('user_id', $userId)
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('sender', 'admin')->whereNull('read_at');
            }])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function($conv) {
                $lastMsg = $conv->messages()->latest()->first();
                return [
                    'id' => $conv->id,
                    'subject' => $conv->subject ?? 'Conversa de suporte',
                    'status' => $conv->status,
                    'last_message' => $lastMsg ? $lastMsg->message : '',
                    'last_message_at' => $conv->last_message_at->format('Y-m-d H:i:s'),
                    'unread_count' => $conv->unread_count
                ];
            });
            
        $totalUnread = $conversations->sum('unread_count');

        return response()->json([
            'unread_count' => $totalUnread,
            'conversations' => $conversations
        ]);
    }

    /**
     * Get messages for a specific conversation and mark as read.
     */
    public function conversationMessages($id)
    {
        $userId = auth()->id();
        
        $conversation = SupportConversation::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();
            
        $messages = SupportChat::with('attachments')
            ->where('conversation_id', $id)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'message', 'sender', 'created_at', 'read_at', 'conversation_id']);

        // Mark admin messages as read
        SupportChat::where('conversation_id', $id)
            ->where('sender', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'subject' => $conversation->subject
            ],
            'messages' => $messages
        ]);
    }

    /**
     * Legacy messages endpoint mapping to latest conversation or empty
     */
    public function messages(Request $request)
    {
        $latestConv = SupportConversation::where('user_id', auth()->id())
            ->orderBy('last_message_at', 'desc')
            ->first();
            
        if (!$latestConv) return response()->json([]);
        
        return $this->conversationMessages($latestConv->id);
    }

    /**
     * User sends a message.
     */
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:2000',
            'sender'  => 'nullable|in:user,admin',
            'context' => 'nullable|string',
            'conversation_id' => 'nullable|exists:support_conversations,id',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,pdf,txt,log,zip,rar|max:5120'
        ]);

        if (!$request->message && !$request->hasFile('attachment')) {
            return response()->json(['success' => false, 'error' => 'Message or attachment required'], 422);
        }

        $userId = auth()->id();
        $sender = $request->input('sender', 'user');
        $conversationId = $request->input('conversation_id');

        // Create new conversation if none provided
        if (!$conversationId) {
            // Check if there's already an active conversation
            $activeConv = SupportConversation::where('user_id', $userId)
                ->whereIn('status', ['open', 'pending', 'answered'])
                ->first();

            if ($activeConv) {
                return response()->json([
                    'success' => false,
                    'error' => 'Você já possui um atendimento em andamento.',
                    'conversation_id' => $activeConv->id
                ], 403);
            }

            $conversation = SupportConversation::create([
                'user_id' => $userId,
                'subject' => 'Nova Conversa',
                'status' => 'open',
                'last_message_at' => now()
            ]);
            $conversationId = $conversation->id;
        } else {
            // Verify ownership
            $conversation = SupportConversation::where('id', $conversationId)->where('user_id', $userId)->firstOrFail();
            
            if ($conversation->isClosed()) {
                return response()->json(['success' => false, 'error' => 'Este atendimento foi encerrado.'], 403);
            }

            $conversation->update([
                'status' => $sender === 'user' ? 'pending' : 'answered',
                'last_message_at' => now()
            ]);
        }

        $chat = SupportChat::create([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'message' => $request->message ?? '',
            'sender'  => $sender,
        ]);

        if ($request->hasFile('attachment')) {
            $this->chatService->processAttachment($chat, $request->file('attachment'), auth()->user());
        }
        
        $chat->load('attachments');

        if ($sender === 'user') {
            $contextArray = [];
            if ($request->input('context')) {
                $contextArray = json_decode($request->input('context'), true) ?? [];
            }
            $this->chatService->notifyAdmin(auth()->user(), $request->message ?? 'Anexo enviado', $contextArray, $conversationId);
        }

        return response()->json([
            'success' => true, 
            'chat' => $chat,
            'conversation_id' => $conversationId
        ]);
    }

    /**
     * Count of unread messages from admin (for badge).
     */
    public function unreadCount()
    {
        $count = SupportChat::where('user_id', auth()->id())
            ->where('sender', 'admin')
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Download attachment securely.
     */
    public function downloadAttachment($uuid)
    {
        $attachment = SupportChatAttachment::with('chat.conversation')->where('uuid', $uuid)->first();

        // Safe 404
        if (!$attachment) {
            abort(404, 'Arquivo não encontrado.');
        }
        
        // Ensure user owns this conversation
        if ($attachment->chat->conversation->user_id !== auth()->id()) {
            logger()->warning('Unauthorized access attempt to support chat attachment', [
                'attachment_uuid' => $uuid,
                'conversation_id' => $attachment->chat->conversation->id ?? null,
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            abort(403, 'Acesso negado.');
        }

        if (!Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        logger()->info('Support chat attachment downloaded by user', [
            'attachment_uuid' => $uuid,
            'conversation_id' => $attachment->chat->conversation->id ?? null,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return Storage::disk($attachment->disk)->response($attachment->path);
    }

    // Extracted notifyAdmin to SupportChatService
}
