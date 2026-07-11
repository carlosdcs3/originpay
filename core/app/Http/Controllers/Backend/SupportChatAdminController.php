<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SupportChat;
use App\Models\SupportChatAttachment;
use App\Models\SupportConversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Services\SupportChatService;

class SupportChatAdminController extends Controller
{
    public function __construct(
        protected SupportChatService $chatService
    ) {}
    /**
     * List all support conversations.
     */
    public function index()
    {
        $conversations = SupportConversation::with('user')
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('sender', 'user')->whereNull('read_at');
            }, 'messages as messages_count'])
            ->with(['messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderBy('last_message_at', 'desc')
            ->get();

        return view('backend.support_chat.index', compact('conversations'));
    }

    /**
     * Show full chat for a specific conversation. Mark user messages as read.
     */
    public function show(int $conversationId)
    {
        $conversation = SupportConversation::with('user')->findOrFail($conversationId);
        $user = $conversation->user;

        $messages = SupportChat::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark user messages as read
        SupportChat::where('conversation_id', $conversationId)
            ->where('sender', 'user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('backend.support_chat.show', compact('conversation', 'user', 'messages'));
    }

    /**
     * Close the conversation.
     */
    public function close(int $conversationId)
    {
        $conversation = SupportConversation::findOrFail($conversationId);
        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by_admin_id' => auth()->guard('admin')->id()
        ]);

        SupportChat::create([
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
            'message' => 'Atendimento encerrado por ' . (auth()->guard('admin')->user()->name ?? 'Administrador') . '.',
            'sender' => 'admin',
            'is_system' => true
        ]);

        if(request()->expectsJson()) { return response()->json(['success'=>true, 'message'=>'Atendimento encerrado com sucesso.']); } return back()->with('success', 'Atendimento encerrado com sucesso.');
    }

    /**
     * Reopen the conversation.
     */
    public function reopen(int $conversationId)
    {
        $conversation = SupportConversation::findOrFail($conversationId);
        $conversation->update([
            'status' => 'open',
            'closed_at' => null,
            'closed_by_admin_id' => null
        ]);

        SupportChat::create([
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
            'message' => 'Atendimento reaberto por ' . (auth()->guard('admin')->user()->name ?? 'Administrador') . '.',
            'sender' => 'admin',
            'is_system' => true
        ]);

        if(request()->expectsJson()) { return response()->json(['success'=>true, 'message'=>'Atendimento reaberto com sucesso.']); } return back()->with('success', 'Atendimento reaberto com sucesso.');
    }

    /**
     * Admin replies to a conversation.
     */
    public function reply(Request $request, int $conversationId)
    {
        $request->validate([
            'message' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,pdf,txt,log,zip,rar|max:5120'
        ]);

        $conversation = SupportConversation::findOrFail($conversationId);
        $user = $conversation->user;

        if (!$request->message && !$request->hasFile('attachment')) {
            return back()->withErrors(['message' => 'Envie uma mensagem ou anexo.']);
        }

        $chat = SupportChat::create([
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'message' => $request->message ?? '',
            'sender'  => 'admin',
        ]);

        if ($request->hasFile('attachment')) {
            $this->chatService->processAttachment($chat, $request->file('attachment'), auth()->guard('admin')->user());
        }

        $conversation->update([
            'status' => 'answered',
            'last_message_at' => now()
        ]);

        // Notify user via email
        $this->chatService->notifyUser($user, $request->message);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'chat' => $chat]);
        }

        return back()->with('success', 'Resposta enviada!');
    }

    /**
     * Total unread chats count (for admin badge).
     */
    public function unreadCount()
    {
        $count = SupportChat::where('sender', 'user')
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get global notifications payload (unread count, pending count, latest chats).
     */
    public function notifications()
    {
        try {
            $unreadCount = SupportChat::where('sender', 'user')
                ->whereNull('read_at')
                ->count();

            $newConversations = SupportConversation::where('status', 'pending')->count();

            $latest = SupportConversation::with('user')
                ->whereHas('messages', function ($q) {
                    $q->where('sender', 'user')->whereNull('read_at');
                })
                ->orderBy('last_message_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($conv) {
                    $user = $conv->user;
                    $lastMsg = $conv->messages()->orderBy('created_at', 'desc')->first();
                    return [
                        'id' => $conv->id,
                        'user_name' => $user->fullname ?? $user->name ?? 'Desconhecido',
                        'user_email' => $user->email ?? '',
                        'message' => $lastMsg ? \Str::limit($lastMsg->message, 50) : '',
                        'created_at' => $lastMsg ? $lastMsg->created_at->diffForHumans() : '',
                        'url' => route('admin.support-chat.show', $conv->id),
                    ];
                });

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
                'new_conversations_count' => $newConversations,
                'latest' => $latest
            ]);
        } catch (\Exception $e) {
            logger()->error('Support chat notifications error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'unread_count' => 0,
                'new_conversations_count' => 0,
                'latest' => [],
                'error' => 'Failed to fetch notifications.'
            ], 500);
        }
    }

    /**
     * Fetch messages for polling.
     */
    public function fetch(int $conversationId)
    {
        $messages = SupportChat::with('attachments')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark user messages as read dynamically when fetched
        SupportChat::where('conversation_id', $conversationId)
            ->where('sender', 'user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    /**
     * Download attachment.
     */
    public function downloadAttachment($uuid)
    {
        $attachment = SupportChatAttachment::where('uuid', $uuid)->first();

        // Safe 404
        if (!$attachment) {
            abort(404, 'Arquivo não encontrado.');
        }

        if (!Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        logger()->info('Support chat attachment downloaded by admin', [
            'attachment_uuid' => $uuid,
            'conversation_id' => $attachment->support_chat_id ? $attachment->chat->support_conversation_id : null,
            'admin_id' => auth()->guard('admin')->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return Storage::disk($attachment->disk)->response($attachment->path);
    }

    // Extracted notifyUser to SupportChatService
}

