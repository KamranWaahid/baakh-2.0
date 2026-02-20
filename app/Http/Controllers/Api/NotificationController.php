<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Returns unread + recent notifications for the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->take(30)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->data['title'] ?? 'New Update',
                    'message' => $n->data['message'] ?? '',
                    'icon' => $n->data['icon'] ?? 'Bell',
                    'color' => $n->data['color'] ?? 'blue',
                    'link' => $n->data['link'] ?? null,
                    'data' => $n->data,
                    'created_at' => $n->created_at,
                    'read_at' => $n->read_at,
                ];
            });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * POST /api/notifications/{id}/read
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/notifications/read-all
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/notifications/clear
     * Delete all notifications for the user.
     */
    public function clear(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json(['success' => true]);
    }
}
