<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileDevice;
use App\Models\MobileNotification;
use App\Models\MobileNotificationReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $lang = $request->query('lang') === 'en' ? 'en' : 'sd';
        $platform = $request->query('platform');
        $audience = $request->user() ? 'signed_in' : 'guests';

        $notifications = MobileNotification::query()
            ->visibleToApp($platform, $audience)
            ->orderByRaw("CASE WHEN priority = 'high' THEN 0 ELSE 1 END")
            ->orderByDesc('schedule_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $receipts = collect();
        if ($request->user()) {
            $receipts = MobileNotificationReceipt::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('mobile_notification_id', $notifications->pluck('id'))
                ->get()
                ->keyBy('mobile_notification_id');
        }

        $items = $notifications->map(function (MobileNotification $notification) use ($lang, $receipts) {
            $payload = $notification->localized($lang);
            $receipt = $receipts->get($notification->id);
            $payload['read_at'] = $receipt?->read_at;
            $payload['opened_at'] = $receipt?->opened_at;

            return $payload;
        });

        return response()->json([
            'data' => $items,
            'unread_count' => $items->whereNull('read_at')->count(),
        ]);
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'string', Rule::in(MobileDevice::PLATFORMS)],
            'provider' => ['nullable', 'string', Rule::in(MobileDevice::PROVIDERS)],
            'device_id' => ['nullable', 'string', 'max:80'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'locale' => ['nullable', 'string', 'in:sd,en'],
            'push_enabled' => ['sometimes', 'boolean'],
        ]);

        $provider = $validated['provider'] ?? ($validated['platform'] === 'ios' ? 'apns' : 'fcm');

        $device = MobileDevice::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()?->id,
                'platform' => $validated['platform'],
                'provider' => $provider,
                'device_id' => $validated['device_id'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'locale' => $validated['locale'] ?? 'sd',
                'push_enabled' => $request->boolean('push_enabled', true),
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device registered',
            'data' => [
                'id' => $device->id,
                'platform' => $device->platform,
                'provider' => $device->provider,
                'push_enabled' => $device->push_enabled,
            ],
        ]);
    }

    public function unregisterDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required_without:device_id', 'nullable', 'string', 'max:512'],
            'device_id' => ['required_without:token', 'nullable', 'string', 'max:80'],
        ]);

        $query = MobileDevice::query();
        if (!empty($validated['token'])) {
            $query->where('token', $validated['token']);
        }
        if (!empty($validated['device_id'])) {
            $query->where('device_id', $validated['device_id']);
        }
        if ($request->user()) {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)->orWhereNull('user_id');
            });
        }

        $updated = $query->update(['push_enabled' => false]);

        return response()->json([
            'message' => 'Device unregistered',
            'updated' => $updated,
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = MobileNotification::findOrFail($id);
        $receipt = $this->touchReceipt($request, $notification, 'read');

        return response()->json([
            'success' => true,
            'read_at' => $receipt->read_at,
        ]);
    }

    public function markOpened(Request $request, int $id): JsonResponse
    {
        $notification = MobileNotification::findOrFail($id);
        $alreadyOpened = MobileNotificationReceipt::query()
            ->where('mobile_notification_id', $notification->id)
            ->when($request->user(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->whereNotNull('opened_at')
            ->exists();

        $receipt = $this->touchReceipt($request, $notification, 'opened');

        if (!$alreadyOpened && $receipt->opened_at) {
            $notification->increment('open_count');
        }

        return response()->json([
            'success' => true,
            'opened_at' => $receipt->opened_at,
        ]);
    }

    private function touchReceipt(Request $request, MobileNotification $notification, string $event): MobileNotificationReceipt
    {
        $deviceId = $request->input('device_id');
        $device = null;
        if ($deviceId) {
            $device = MobileDevice::where('device_id', $deviceId)->first();
        }

        $receipt = MobileNotificationReceipt::firstOrNew([
            'mobile_notification_id' => $notification->id,
            'mobile_device_id' => $device?->id,
        ]);

        $receipt->user_id = $request->user()?->id ?? $receipt->user_id;
        $receipt->sent_at = $receipt->sent_at ?? now();
        $receipt->status = $event === 'opened' ? 'opened' : ($receipt->status ?: 'delivered');

        if ($event === 'read' && !$receipt->read_at) {
            $receipt->read_at = now();
        }
        if ($event === 'opened') {
            $receipt->read_at = $receipt->read_at ?? now();
            if (!$receipt->opened_at) {
                $receipt->opened_at = now();
            }
        }

        $receipt->save();

        return $receipt;
    }
}
