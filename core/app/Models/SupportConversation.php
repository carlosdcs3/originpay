<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportConversation extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'status', // open, pending, answered, closed
        'last_message_at',
        'closed_at',
        'assigned_admin_id',
        'closed_by_admin_id'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportChat::class, 'conversation_id', 'id');
    }

    public function unreadCount(string $role = 'user'): int
    {
        $sender = $role === 'user' ? 'admin' : 'user';
        return $this->messages()->where('sender', $sender)->whereNull('read_at')->count();
    }
    
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function closedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'closed_by_admin_id');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'pending', 'answered']);
    }
}
