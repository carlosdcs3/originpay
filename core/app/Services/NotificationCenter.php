<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\GlobalPlatformNotification;

class NotificationCenter
{
    /**
     * Create a new notification for a specific user.
     *
     * @param User $user
     * @param array $data
     * @return void
     */
    public static function create(User $user, array $data)
    {
        // Enforce basic standard properties
        $payload = [
            'module'     => $data['module'] ?? 'system',
            'type'       => $data['type'] ?? 'info',
            'title'      => $data['title'] ?? '',
            'message'    => $data['message'] ?? '',
            'icon'       => $data['icon'] ?? self::getDefaultIcon($data['module'] ?? 'system'),
            'severity'   => $data['severity'] ?? 'info',
            'action_url' => $data['action_url'] ?? null,
            'metadata'   => $data['metadata'] ?? [],
        ];

        $user->notify(new GlobalPlatformNotification($payload));
    }

    /**
     * Get a default icon based on the module if none is provided.
     *
     * @param string $module
     * @return string
     */
    protected static function getDefaultIcon(string $module): string
    {
        return match (strtolower($module)) {
            'financeiro' => 'fas fa-money-bill-wave',
            'gateway'    => 'fas fa-credit-card',
            'connect'    => 'fas fa-rocket',
            'seguranca'  => 'fas fa-shield-alt',
            'security'   => 'fas fa-shield-alt',
            'sistema'    => 'fas fa-cog',
            'system'     => 'fas fa-cog',
            default      => 'fas fa-bell',
        };
    }
}
