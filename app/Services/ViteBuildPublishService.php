<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Keep document-root /build in sync with Laravel public/build.
 * On cPanel, Vite hashes are read from APP_PATH/public/build but browsers
 * fetch /build/* from public_html — those trees must match after deploy/cache clear.
 */
class ViteBuildPublishService
{
    public function manifestFingerprint(): ?string
    {
        $manifest = public_path('build/manifest.json');
        if (!is_file($manifest)) {
            return null;
        }

        return substr(hash_file('sha256', $manifest) ?: '', 0, 12);
    }

    public function documentRoot(): ?string
    {
        $candidates = array_filter([
            env('MEDIA_WEB_ROOT'),
            env('PUBLIC_HTML_PATH'),
            $_SERVER['DOCUMENT_ROOT'] ?? null,
        ]);

        foreach ($candidates as $path) {
            $path = rtrim((string) $path, '/');
            if ($path !== '' && is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array{published: bool, source: string, target: ?string, fingerprint: ?string, message: string}
     */
    public function publishToDocumentRoot(): array
    {
        $source = public_path('build');
        $fingerprint = $this->manifestFingerprint();
        $docRoot = $this->documentRoot();

        if (!is_dir($source) || !is_file($source.'/manifest.json')) {
            return [
                'published' => false,
                'source' => $source,
                'target' => null,
                'fingerprint' => $fingerprint,
                'message' => 'No Vite build found in public/build.',
            ];
        }

        if ($docRoot === null) {
            return [
                'published' => false,
                'source' => $source,
                'target' => null,
                'fingerprint' => $fingerprint,
                'message' => 'Document root unknown; set MEDIA_WEB_ROOT or PUBLIC_HTML_PATH in .env.',
            ];
        }

        $target = $docRoot.'/build';
        $sourceReal = realpath($source) ?: $source;
        $targetReal = is_dir($target) ? (realpath($target) ?: $target) : $target;

        if ($sourceReal === $targetReal) {
            return [
                'published' => true,
                'source' => $source,
                'target' => $target,
                'fingerprint' => $fingerprint,
                'message' => 'Vite build already served from document root.',
            ];
        }

        File::ensureDirectoryExists($target);
        File::copyDirectory($source, $target);

        // Remove stale hashed files that are no longer in the new build.
        $keep = $this->listedBuildFiles($source);
        foreach (File::allFiles($target) as $file) {
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($target))), '/');
            if ($relative !== '' && !isset($keep[$relative])) {
                File::delete($file->getPathname());
            }
        }

        return [
            'published' => true,
            'source' => $source,
            'target' => $target,
            'fingerprint' => $fingerprint,
            'message' => 'Published Vite build to document root.',
        ];
    }

    /**
     * @return array<string, true>
     */
    private function listedBuildFiles(string $buildPath): array
    {
        $keep = ['manifest.json' => true];
        $manifest = json_decode((string) file_get_contents($buildPath.'/manifest.json'), true);
        if (!is_array($manifest)) {
            return $keep;
        }

        foreach ($manifest as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            foreach (['file', 'css', 'assets'] as $key) {
                foreach ((array) ($entry[$key] ?? []) as $file) {
                    if (is_string($file) && $file !== '') {
                        $keep[ltrim($file, '/')] = true;
                    }
                }
            }
        }

        return $keep;
    }
}
