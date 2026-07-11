<?php

namespace App\Services;

use App\Models\SupportChat;
use App\Models\SupportChatAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

class SupportChatService
{
    /**
     * Process and store an attachment for a chat message.
     */
    public function processAttachment(SupportChat $chat, UploadedFile $file, $uploadedBy)
    {
        $path = $file->store('support_attachments', 'local');
        
        return SupportChatAttachment::create([
            'support_chat_id' => $chat->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by_type' => get_class($uploadedBy),
            'uploaded_by_id' => clone $uploadedBy->id ?? $uploadedBy->getKey()
        ]);
    }

    /**
     * Notify Admin of a new message from a User.
     */
    public function notifyAdmin(User $user, string $message, array $context = [], $conversationId = null): void
    {
        $admin = \App\Models\Admin::query()->first();
        if (!$admin) return;

        $contextHtml = '';
        if (!empty($context)) {
            $contextHtml = "<div style='margin-top: 16px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 6px; font-size: 0.8rem; color: #64748b;'>
                <strong>Contexto Automático:</strong><br>
                Navegador: {$context['userAgent']}<br>
                URL: {$context['url']}<br>
                Idioma: {$context['language']}
            </div>";
        }

        try {
            Mail::send([], [], function ($mail) use ($admin, $user, $message, $contextHtml, $conversationId) {
                $routeId = $conversationId ? $conversationId : $user->id; // fallback if needed
                $mail->to($admin->email)
                    ->subject('Nova mensagem de suporte — ' . $user->fullname)
                    ->html("
                        <div style='font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; background: #0C0C18; color: #EEEEF8; padding: 32px; border-radius: 12px;'>
                            <h2 style='color: #00D4AA; margin-bottom: 8px;'>Nova mensagem de suporte</h2>
                            <p style='color: #8888A8;'>Usuário: <strong style='color: #EEEEF8;'>{$user->name}</strong> ({$user->email})</p>
                            <div style='background: #111120; border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 16px; margin: 16px 0;'>
                                <p style='color: #EEEEF8; margin: 0;'>{$message}</p>
                            </div>
                            {$contextHtml}
                            <a href='" . route('admin.support-chat.show', $routeId) . "' style='display: inline-block; background: #00D4AA; color: #080810; padding: 12px 24px; border-radius: 50px; text-decoration: none; font-weight: 700; margin-top: 20px;'>Responder no painel</a>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            logger()->warning('Support chat email to admin failed: ' . $e->getMessage());
        }
    }

    /**
     * Notify User of a new message from Admin.
     */
    public function notifyUser(User $user, string $message): void
    {
        try {
            Mail::send([], [], function ($mail) use ($user, $message) {
                $mail->to($user->email)
                    ->subject('Resposta do suporte OriginPay')
                    ->html("
                        <div style='font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; background: #0C0C18; color: #EEEEF8; padding: 32px; border-radius: 12px;'>
                            <h2 style='color: #00D4AA; margin-bottom: 8px;'>Resposta do suporte</h2>
                            <p style='color: #8888A8;'>Olá, <strong style='color: #EEEEF8;'>{$user->fullname}</strong>! Nossa equipe respondeu sua mensagem:</p>
                            <div style='background: #111120; border: 1px solid rgba(0,212,170,0.2); border-radius: 8px; padding: 16px; margin: 16px 0;'>
                                <p style='color: #EEEEF8; margin: 0;'>{$message}</p>
                            </div>
                            <p style='color: #8888A8; font-size: 0.85rem;'>Para continuar a conversa, acesse o chat de suporte na sua dashboard.</p>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            logger()->warning('Support chat email to user failed: ' . $e->getMessage());
        }
    }
}
