<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GlobalPlatformNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Future: add 'broadcast' channel here
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'module'     => $this->data['module'] ?? 'system',
            'type'       => $this->data['type'] ?? 'info',
            'title'      => $this->data['title'] ?? '',
            'message'    => $this->data['message'] ?? '',
            'icon'       => $this->data['icon'] ?? 'fas fa-bell',
            'severity'   => $this->data['severity'] ?? 'info', // info, success, warning, error
            'action_url' => $this->data['action_url'] ?? null,
            'metadata'   => $this->data['metadata'] ?? [],
        ];
    }
}
