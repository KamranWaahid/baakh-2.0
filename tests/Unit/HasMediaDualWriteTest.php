<?php

namespace Tests\Unit;

use App\Traits\HasMedia;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HasMediaDualWriteTest extends TestCase
{
    public function test_local_upload_writes_to_media_web_root_as_well_as_public(): void
    {
        $webRoot = storage_path('framework/testing/media-web-root-' . uniqid());
        mkdir($webRoot, 0755, true);

        config([
            'media.disk' => 'local',
            'media.base_path' => 'assets/images',
            'media.web_root' => $webRoot,
            'media.max_edge' => 2000,
            'admin_media.sizes' => [
                'small' => ['width' => 100, 'height' => 100],
                'medium' => ['width' => 196, 'height' => 196],
            ],
        ]);

        $uploader = new class {
            use HasMedia;
        };

        $file = UploadedFile::fake()->image('poet.jpg', 400, 400);
        $result = $uploader->uploadImage($file, 'poets', 'dual-write-poet', true);

        $this->assertFalse($result['error'] ?? true, $result['message'] ?? 'upload failed');
        $this->assertSame('assets/images/poets/dual-write-poet.webp', $result['full_path']);

        $publicFile = public_path($result['full_path']);
        $webFile = $webRoot . '/' . $result['full_path'];

        try {
            $this->assertFileExists($publicFile);
            $this->assertFileExists($webFile);
            $this->assertGreaterThan(0, filesize($publicFile));
            $this->assertSame(filesize($publicFile), filesize($webFile));

            $this->assertFileExists(public_path('assets/images/poets/dual-write-poet_small.webp'));
            $this->assertFileExists($webRoot . '/assets/images/poets/dual-write-poet_small.webp');
        } finally {
            foreach ([
                $result['full_path'],
                'assets/images/poets/dual-write-poet_small.webp',
                'assets/images/poets/dual-write-poet_medium.webp',
            ] as $relative) {
                @unlink(public_path($relative));
                @unlink($webRoot . '/' . $relative);
            }
            @rmdir($webRoot . '/assets/images/poets');
            @rmdir($webRoot . '/assets/images');
            @rmdir($webRoot . '/assets');
            @rmdir($webRoot);
        }
    }

    public function test_update_image_uses_unique_filename_so_url_changes(): void
    {
        config([
            'media.disk' => 'local',
            'media.base_path' => 'assets/images',
            'media.web_root' => '',
            'media.max_edge' => 2000,
            'admin_media.sizes' => [
                'small' => ['width' => 100, 'height' => 100],
            ],
        ]);

        $uploader = new class {
            use HasMedia;
        };

        $first = UploadedFile::fake()->image('poet-a.jpg', 400, 400);
        $created = $uploader->uploadImage($first, 'poets', 'replace-poet', true);
        $this->assertFalse($created['error'] ?? true, $created['message'] ?? 'upload failed');
        $this->assertSame('assets/images/poets/replace-poet.webp', $created['full_path']);

        $second = UploadedFile::fake()->image('poet-b.jpg', 400, 400);
        $updated = $uploader->updateImage($second, 'poets', $created['full_path'], 'replace-poet', true);

        try {
            $this->assertFalse($updated['error'] ?? true, $updated['message'] ?? 'update failed');
            $this->assertNotSame($created['full_path'], $updated['full_path']);
            $this->assertMatchesRegularExpression(
                '#^assets/images/poets/replace-poet-[a-f0-9]{8}\.webp$#',
                $updated['full_path']
            );
            $this->assertFileDoesNotExist(public_path($created['full_path']));
            $this->assertFileExists(public_path($updated['full_path']));
        } finally {
            foreach (array_filter([
                $created['full_path'] ?? null,
                $updated['full_path'] ?? null,
                'assets/images/poets/replace-poet_small.webp',
                isset($updated['full_path'])
                    ? preg_replace('/\.webp$/', '_small.webp', $updated['full_path'])
                    : null,
            ]) as $relative) {
                @unlink(public_path($relative));
            }
        }
    }
}
