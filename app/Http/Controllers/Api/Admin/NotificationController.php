<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Poetry;
use App\Models\Poets;
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
            ->get()
            ->map(fn (AdminNotification $n) => $this->serialize($n));

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

    private function serialize(AdminNotification $n): array
    {
        $data = is_array($n->data) ? $n->data : [];
        $data = $this->enrichEntityData($data, $n->message);

        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'message' => $this->readableMessage($n->message, $data),
            'icon' => $n->icon,
            'color' => $n->color,
            'link' => $n->link,
            'data' => $data,
            'created_at' => $n->created_at,
            'read_at' => $n->read_at,
        ];
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

        // Parse older free-text messages: "slug" by Poet has been published.
        if (empty($data['entity_name']) && is_string($message) && preg_match('/"([^"]+)"/', $message, $m)) {
            $data['entity_name'] = $m[1];
        }
        if (empty($data['poet_name']) && is_string($message) && preg_match('/\bby\s+(.+?)(?:\s+has\b|$)/iu', $message, $m)) {
            $data['poet_name'] = trim($m[1], " \t\n\r\0\x0B\"“”");
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

        // Soften slug-looking quoted segments in legacy messages.
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
