<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class PoetImageUrl
{
    /**
     * Resolve a poet_pic DB value to a browser-loadable URL (local path or CDN).
     */
    public static function resolve(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $relative = ltrim($value, '/');
        if ($relative === '') {
            return null;
        }

        if (File::exists(public_path($relative))) {
            return '/' . $relative;
        }

        $cloudBaseUrl = rtrim((string) config('filesystems.disks.s3.url', ''), '/');
        if ($cloudBaseUrl !== '') {
            $candidates = self::pathCandidates($relative);
            $first = $candidates[0] ?? $relative;

            return $cloudBaseUrl . '/' . ltrim($first, '/');
        }

        // Match admin preview: expose web-root path when object is not on local disk.
        return '/' . $relative;
    }

    /**
     * @return list<string>
     */
    public static function pathCandidates(string $relative): array
    {
        $relative = ltrim($relative, '/');
        $fileName = basename($relative);
        $dir = trim(dirname($relative), '.');
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);

        $legacyBase = preg_replace('/_[a-f0-9]{8,}_opt$/i', '', $baseName) ?? $baseName;
        $legacyBase = preg_replace('/_opt$/i', '', $legacyBase) ?? $legacyBase;

        $isOptimizedVariant = str_contains(strtolower($baseName), '_opt');

        $nameCandidates = array_values(array_unique([
            $isOptimizedVariant ? ($legacyBase . '_small.jpg') : $fileName,
            $fileName,
            $legacyBase . '_small.jpg',
            $legacyBase . '.jpg',
            $legacyBase . '.jpeg',
            $legacyBase . '.png',
            $legacyBase . '.webp',
        ]));

        $dirCandidates = array_values(array_unique(array_filter([
            $isOptimizedVariant ? 'Images' : null,
            $dir !== '' ? $dir : null,
            'assets/images/poets',
            'assets/Images/poets',
            'Images',
            'images',
        ])));

        $paths = [$relative];
        foreach ($dirCandidates as $dirCandidate) {
            foreach ($nameCandidates as $nameCandidate) {
                $paths[] = trim($dirCandidate, '/') . '/' . $nameCandidate;
            }
        }

        return array_values(array_unique($paths));
    }
}
