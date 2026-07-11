<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportChat extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'message', 'sender', 'read_at', 'is_system'];

    protected $casts = [
        'read_at' => 'datetime',
        'is_system' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromUser(): bool
    {
        return $this->sender === 'user';
    }

    public function isFromAdmin(): bool
    {
        return $this->sender === 'admin';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class);
    }
    
    public function attachments()
    {
        return $this->hasMany(SupportChatAttachment::class, 'support_chat_id');
    }
}
