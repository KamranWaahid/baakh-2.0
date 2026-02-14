<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/admin/notifications
     * Returns unread + recent notifications for the bell dropdown.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = AdminNotification::orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        $unreadCount = AdminNotification::unread()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * POST /api/admin/notifications/{id}/read
     * Mark a single notification as read.
     */
    public function markRead(AdminNotification $notification): JsonResponse
    {
        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/admin/notifications/read-all
     * Mark all notifications as read.
     */
    public function markAllRead(): JsonResponse
    {
        AdminNotification::unread()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/admin/notifications/clear
     * Clear all notifications.
     */
    public function clear(): JsonResponse
    {
        AdminNotification::truncate();

        return response()->json(['success' => true]);
    }
}
