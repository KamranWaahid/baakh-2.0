<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileDevice;
use App\Models\MobileNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileNotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_mobile_notifications')->only(['index', 'show']);
        $this->middleware('can:manage_mobile_notifications')->only(['store', 'update', 'destroy', 'publish']);
    }

    public function index(Request $request)
    {
        $query = MobileNotification::query()->with('creator:id,name,username');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title_sd', 'like', "%{$search}%")
                    ->orWhere('title_en', 'like', "%{$search}%")
                    ->orWhere('body_sd', 'like', "%{$search}%")
                    ->orWhere('body_en', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type') && in_array($request->type, MobileNotification::TYPES, true)) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status') && in_array($request->status, MobileNotification::STATUSES, true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform') && in_array($request->platform, MobileNotification::PLATFORMS, true)) {
            $query->where('platforms', 'like', '%"'.$request->platform.'"%');
        }

        $notifications = $query->orderByDesc('id')->paginate($request->integer('per_page', 12));

        return response()->json(array_merge([
            'notifications' => $notifications,
            'stats' => [
                'draft' => MobileNotification::where('status', 'draft')->count(),
                'scheduled' => MobileNotification::where('status', 'scheduled')->count(),
                'published' => MobileNotification::where('status', 'published')->count(),
                'devices_android' => MobileDevice::where('platform', 'android')->where('push_enabled', true)->count(),
                'devices_ios' => MobileDevice::where('platform', 'ios')->where('push_enabled', true)->count(),
            ],
        ], MobileNotification::metaOptions()));
    }

    public function show(int $id)
    {
        $notification = MobileNotification::with('creator:id,name,username')->findOrFail($id);

        return response()->json($notification);
    }

    public function store(Request $request)
    {
        $notification = MobileNotification::create($this->validatedPayload($request) + [
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Mobile notification created',
            'data' => $notification->fresh(),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $notification = MobileNotification::findOrFail($id);
        $notification->update($this->validatedPayload($request, $notification));

        return response()->json([
            'message' => 'Mobile notification updated',
            'data' => $notification->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        MobileNotification::findOrFail($id)->delete();

        return response()->json(['message' => 'Mobile notification deleted']);
    }

    public function publish(int $id)
    {
        $notification = MobileNotification::findOrFail($id);
        $notification->update([
            'status' => 'published',
            'is_active' => true,
            'sent_at' => $notification->sent_at ?? now(),
        ]);

        return response()->json([
            'message' => 'Mobile notification published',
            'data' => $notification->fresh(),
        ]);
    }

    private function validatedPayload(Request $request, ?MobileNotification $notification = null): array
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(MobileNotification::TYPES)],
            'title_sd' => ['required', 'string', 'max:120'],
            'title_en' => ['nullable', 'string', 'max:120'],
            'body_sd' => ['required', 'string', 'max:500'],
            'body_en' => ['nullable', 'string', 'max:500'],
            'cta_sd' => ['nullable', 'string', 'max:60'],
            'cta_en' => ['nullable', 'string', 'max:60'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:30'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', Rule::in(MobileNotification::PLATFORMS)],
            'audience' => ['required', 'string', Rule::in(MobileNotification::AUDIENCES)],
            'audience_filter' => ['nullable', 'array'],
            'deep_link' => ['nullable', 'string', 'max:255'],
            'web_path' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'string', Rule::in(MobileNotification::PRIORITIES)],
            'status' => ['required', 'string', Rule::in(MobileNotification::STATUSES)],
            'is_active' => ['sometimes', 'boolean'],
            'schedule_at' => ['nullable', 'date'],
            'recurrence' => ['required', 'string', Rule::in(MobileNotification::RECURRENCES)],
            'recurrence_time' => ['nullable', 'date_format:H:i'],
            'expires_at' => ['nullable', 'date'],
            'data' => ['nullable', 'array'],
        ]);

        $meta = MobileNotification::TYPE_META[$validated['type']] ?? [];
        $validated['icon'] = $validated['icon'] ?? $notification?->icon ?? ($meta['icon'] ?? 'Bell');
        $validated['color'] = $validated['color'] ?? $notification?->color ?? ($meta['color'] ?? 'blue');
        $validated['platforms'] = array_values(array_unique($validated['platforms']));
        $validated['is_active'] = $request->boolean('is_active', $notification?->is_active ?? true);
        $validated['title_sd'] = strip_tags($validated['title_sd']);
        $validated['title_en'] = isset($validated['title_en']) ? strip_tags($validated['title_en']) : null;
        $validated['body_sd'] = strip_tags($validated['body_sd']);
        $validated['body_en'] = isset($validated['body_en']) ? strip_tags($validated['body_en']) : null;

        if (($validated['status'] ?? '') === 'scheduled' && empty($validated['schedule_at'])) {
            $validated['status'] = 'draft';
        }

        return $validated;
    }
}
