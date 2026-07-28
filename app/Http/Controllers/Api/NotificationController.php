<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poetry;
use App\Models\Poets;
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
            ->latest()
            ->take(30)
            ->get()
            ->map(function ($n) {
                $data = is_array($n->data) ? $n->data : [];
                $data = $this->enrichEntityData($data, $data['message'] ?? null);

                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $data['title'] ?? 'Update',
                    'message' => $this->readableMessage($data['message'] ?? '', $data),
                    'icon' => $data['icon'] ?? 'Bell',
                    'color' => $data['color'] ?? 'blue',
                    'link' => $data['link'] ?? null,
                    'data' => $data,
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

    private function enrichEntityData(array $data, ?string $message): array
    {
        $entityType = $data['entity_type'] ?? null;
        $entityId = $data['entity_id'] ?? null;
        $entityName = trim((string) ($data['entity_name'] ?? ''));

        if ($entityType === 'poetry' && $entityId && ($entityName === '' || $this->looksLikeSlug($entityName))) {
            $poetry = Poetry::with(['translations', 'poet'])->find($entityId);
            if ($poetry) {
                $title = $poetry->translations->firstWhere('lang', 'sd')?->title
                    ?: $poetry->translations->first()?->title;
                if ($title) {
                    $data['entity_name'] = $title;
                }
                if (empty($data['poet_name']) && $poetry->poet) {
                    $poetName = $poetry->poet->all_details()->where('lang', 'sd')->value('poet_laqab')
                        ?: $poetry->poet->all_details()->value('poet_laqab');
                    if ($poetName) {
                        $data['poet_name'] = $poetName;
                    }
                }
            }
        }

        if ($entityType === 'poet' && $entityId && ($entityName === '' || $this->looksLikeSlug($entityName))) {
            $poet = Poets::find($entityId);
            if ($poet) {
                $name = $poet->all_details()->where('lang', 'sd')->value('poet_laqab')
                    ?: $poet->all_details()->value('poet_laqab');
                if ($name) {
                    $data['entity_name'] = $name;
                }
            }
        }

        if (empty($data['entity_name']) && is_string($message) && preg_match('/"([^"]+)"/', $message, $m)) {
            $data['entity_name'] = $m[1];
        }
        if (empty($data['poet_name']) && is_string($message) && preg_match('/\bby\s+(.+?)(?:\s+has\b|$)/iu', $message, $m)) {
            $data['poet_name'] = trim($m[1], " \t\n\r\0\x0B\"“”");
        }

        if (!empty($data['entity_name']) && $this->looksLikeSlug($data['entity_name'])) {
            $data['entity_name'] = str_replace('-', ' ', $data['entity_name']);
        }

        return $data;
    }

    private function readableMessage(?string $message, array $data): string
    {
        $message = trim((string) $message);
        $entity = trim((string) ($data['entity_name'] ?? ''));
        $poet = trim((string) ($data['poet_name'] ?? ''));

        if ($entity !== '' && $poet !== '') {
            return "{$entity} · {$poet}";
        }
        if ($entity !== '') {
            return $entity;
        }

        if ($message !== '' && preg_match('/"([^"]+)"/', $message, $m) && $this->looksLikeSlug($m[1])) {
            $human = str_replace('-', ' ', $m[1]);
            $message = str_replace('"' . $m[1] . '"', '"' . $human . '"', $message);
        }

        return $message;
    }

    private function looksLikeSlug(string $value): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)+$/i', trim($value));
    }
}
