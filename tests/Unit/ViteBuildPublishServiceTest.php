<?php

namespace Tests\Unit;

use App\Services\ViteBuildPublishService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViteBuildPublishServiceTest extends TestCase
{
    public function test_publish_replaces_stale_hashed_assets_on_document_root(): void
    {
        $source = sys_get_temp_dir().'/baakh-vite-src-'.uniqid();
        $docRoot = sys_get_temp_dir().'/baakh-vite-doc-'.uniqid();
        File::ensureDirectoryExists($source.'/assets');
        File::ensureDirectoryExists($docRoot.'/build/assets');

        file_put_contents($source.'/manifest.json', json_encode([
            'resources/js/web/main.jsx' => [
                'file' => 'assets/main-new.js',
                'css' => ['assets/app-new.css'],
            ],
        ]));
        file_put_contents($source.'/assets/main-new.js', 'console.log("new")');
        file_put_contents($source.'/assets/app-new.css', 'body{}');
        file_put_contents($docRoot.'/build/manifest.json', json_encode([
            'resources/js/web/main.jsx' => ['file' => 'assets/main-old.js'],
        ]));
        file_put_contents($docRoot.'/build/assets/main-old.js', 'console.log("old")');

        $service = new class($source, $docRoot) extends ViteBuildPublishService {
            public function __construct(
                private string $buildPath,
                private string $webRoot,
            ) {
            }

            public function documentRoot(): ?string
            {
                return $this->webRoot;
            }

            protected function sourceBuildPath(): string
            {
                return $this->buildPath;
            }
        };

        $result = $service->publishToDocumentRoot();

        $this->assertTrue($result['published']);
        $this->assertFileExists($docRoot.'/build/assets/main-new.js');
        $this->assertFileExists($docRoot.'/build/assets/app-new.css');
        $this->assertFileDoesNotExist($docRoot.'/build/assets/main-old.js');

        File::deleteDirectory($source);
        File::deleteDirectory($docRoot);
    }
}
