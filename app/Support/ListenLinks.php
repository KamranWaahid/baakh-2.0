<?php

namespace App\Support;

use Illuminate\Http\Request;

class ListenLinks
{
    public const KEYS = ['youtube', 'spotify', 'deezer'];

    public static function rules(): array
    {
        return [
            'listen_links' => 'nullable|array',
            'listen_links.youtube' => 'nullable|string|max:1000',
            'listen_links.spotify' => 'nullable|string|max:1000',
            'listen_links.deezer' => 'nullable|string|max:1000',
            'youtube_url' => 'nullable|string|max:1000',
            'spotify_url' => 'nullable|string|max:1000',
            'deezer_url' => 'nullable|string|max:1000',
        ];
    }

    public static function fromRequest(Request $request, array $validated = []): ?array
    {
        $raw = $validated['listen_links'] ?? $request->input('listen_links');

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : null;
        }

        $links = is_array($raw) ? $raw : [];

        foreach (self::KEYS as $key) {
            $flatKey = "{$key}_url";
            if (array_key_exists($flatKey, $validated) || $request->exists($flatKey)) {
                $links[$key] = $validated[$flatKey] ?? $request->input($flatKey);
            }
        }

        return self::normalize($links);
    }

    public static function normalize(mixed $links): ?array
    {
        if (is_string($links)) {
            $decoded = json_decode($links, true);
            $links = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($links)) {
            return null;
        }

        $out = [];
        foreach (self::KEYS as $key) {
            $url = trim((string) ($links[$key] ?? ''));
            if ($url !== '') {
                $out[$key] = $url;
            }
        }

        return $out ?: null;
    }

    public static function forApi(?array $links, ?string $fallbackYoutube = null): ?array
    {
        $normalized = self::normalize($links) ?? [];

        if (empty($normalized['youtube']) && self::isYoutube($fallbackYoutube)) {
            $normalized['youtube'] = $fallbackYoutube;
        }

        return $normalized ?: null;
    }

    public static function flat(?array $links): array
    {
        $normalized = self::normalize($links) ?? [];

        return [
            'youtube_url' => $normalized['youtube'] ?? '',
            'spotify_url' => $normalized['spotify'] ?? '',
            'deezer_url' => $normalized['deezer'] ?? '',
        ];
    }

    public static function isYoutube(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        $u = strtolower($url);

        return str_contains($u, 'youtube.com') || str_contains($u, 'youtu.be');
    }
}
