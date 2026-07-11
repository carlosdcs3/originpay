<?php

namespace App\Services;

use App\Models\Dispute;
use App\Models\DisputeEvent;
use Illuminate\Support\Facades\Auth;

class DisputeEventService
{
    public function log(Dispute $dispute, string $type, string $title, ?string $description = null, array $metadata = []): DisputeEvent
    {
        return $dispute->events()->create([
            'event_type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => array_merge($metadata, [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => Auth::id() ?? 1, // Fallback for testing
                'source' => 'admin_panel',
                'merchant_id' => $dispute->merchant_id,
            ])
        ]);
    }
}
