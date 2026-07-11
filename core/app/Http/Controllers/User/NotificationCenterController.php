<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotificationCenterController extends Controller
{
    /**
     * Get the notifications for the current user.
     * Supports filtering by module and pagination.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = $user->notifications();
        
        if ($request->has('module') && $request->module !== 'all') {
            // Because data is JSON, we can query it depending on DB type.
            // But since this is a general setup, we can filter using JSON where.
            $module = $request->module;
            $query->where('data->module', $module);
        }

        $notifications = $query->paginate(20);

        // Group by date to make it easy for frontend rendering if returning HTML or JSON
        // We will return a JSON structure grouping them
        
        $grouped = [
            'today' => [],
            'yesterday' => [],
            'last_7_days' => [],
            'older' => []
        ];

        foreach ($notifications as $notification) {
            $date = $notification->created_at;
            $item = [
                'id' => $notification->id,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $date->toIso8601String(),
                'diff_for_humans' => $date->diffForHumans(),
            ];

            if ($date->isToday()) {
                $grouped['today'][] = $item;
            } elseif ($date->isYesterday()) {
                $grouped['yesterday'][] = $item;
            } elseif ($date->greaterThanOrEqualTo(Carbon::now()->subDays(7))) {
                $grouped['last_7_days'][] = $item;
            } else {
                $grouped['older'][] = $item;
            }
        }

        return response()->json([
            'status' => 'success',
            'unread_count' => $user->unreadNotifications()->count(),
            'data' => [
                'grouped' => $grouped,
                'has_more' => $notifications->hasMorePages(),
                'current_page' => $notifications->currentPage(),
            ]
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error', 'message' => 'Notification not found'], 404);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['status' => 'success']);
    }
}
