<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Transaction;
use App\Notifications\TemplateNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

class TransactionNotifierService
{
    /**
     * Notify a user using a specific template.
     */
    public function toUser(Transaction $trx, string $identifier, array $data, $action = null): void
    {
        $trx->user->notify(new TemplateNotification(
            identifier: $identifier,
            data: $data,
            action: $action
        ));
    }

    /**
     * Notify all admins who have specific permission using a template.
     */
    public function toAdmins(string $permission, string $identifier, array $data, $sender = null, ?string $action = null): void
    {
        $permissionExists = Permission::query()
            ->where('name', $permission)
            ->where('guard_name', 'admin')
            ->exists();

        if (! $permissionExists) {
            Log::warning('Admin notification permission not found; skipping notification dispatch.', [
                'permission' => $permission,
                'identifier' => $identifier,
            ]);

            return;
        }

        $admins = Admin::permission($permission)->get();

        Notification::send($admins, new TemplateNotification(
            identifier: $identifier,
            data: $data,
            sender: $sender,
            action: $action
        ));
    }
}
