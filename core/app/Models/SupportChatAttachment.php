<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportChatAttachment extends Model
{
    protected $fillable = [
        'support_chat_id',
        'uuid',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by_type',
        'uploaded_by_id'
    ];

    protected $hidden = [
        'id',
        'support_chat_id',
        'disk',
        'path',
        'uploaded_by_type',
        'uploaded_by_id',
        'created_at',
        'updated_at'
    ];

    protected $appends = ['url'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attachment) {
            if (empty($attachment->uuid)) {
                $attachment->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getUrlAttribute()
    {
        if (request()->is('admin/*')) {
            return route('admin.support-chat.attachment.download', $this->uuid);
        }
        return route('user.support-chat.attachment.download', $this->uuid);
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(SupportChat::class, 'support_chat_id');
    }
    
    public function uploader()
    {
        return $this->morphTo('uploaded_by');
    }
}
