<?php

namespace Tests\Unit;

use App\Support\PoetImageUrl;
use Tests\TestCase;

class PoetImageUrlTest extends TestCase
{
    public function test_returns_null_for_empty_value(): void
    {
        $this->assertNull(PoetImageUrl::resolve(null));
        $this->assertNull(PoetImageUrl::resolve(''));
    }

    public function test_passes_through_absolute_urls(): void
    {
        $url = 'https://cdn.example.com/Images/poets/shah.webp';

        $this->assertSame($url, PoetImageUrl::resolve($url));
    }

    public function test_resolves_local_relative_path(): void
    {
        $relative = 'assets/images/poets/test-poet.webp';
        $absolute = public_path($relative);
        $dir = dirname($absolute);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($absolute, 'x');

        try {
            $this->assertSame('/' . $relative, PoetImageUrl::resolve($relative));
        } finally {
            @unlink($absolute);
        }
    }

    public function test_falls_back_to_web_root_when_not_on_disk_and_no_cdn(): void
    {
        config(['filesystems.disks.s3.url' => '']);

        $relative = 'Images/poets/missing-poet.webp';

        $this->assertSame('/' . $relative, PoetImageUrl::resolve($relative));
    }

    public function test_builds_cdn_url_for_cloud_relative_path(): void
    {
        config(['filesystems.disks.s3.url' => 'https://cdn.example.com']);

        $relative = 'Images/poets/shah.webp';

        $this->assertSame(
            'https://cdn.example.com/Images/poets/shah.webp',
            PoetImageUrl::resolve($relative)
        );
    }
}
