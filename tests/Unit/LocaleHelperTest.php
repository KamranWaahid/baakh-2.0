<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LocaleHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../app/Helpers/LocaleHelper.php';
    }

    public function test_parses_browser_accept_language_header(): void
    {
        $this->assertSame('en', resolve_request_locale('en-US,en;q=0.9'));
        $this->assertSame('en', resolve_request_locale('en_US,en;q=0.9'));
    }

    public function test_maps_sindhi_tags_to_sd(): void
    {
        $this->assertSame('sd', resolve_request_locale('sd-PK'));
        $this->assertSame('sd', resolve_request_locale('snd'));
    }

    public function test_falls_back_to_default(): void
    {
        $this->assertSame('sd', resolve_request_locale('', 'sd'));
        $this->assertSame('en', resolve_request_locale(null, 'en'));
    }
}
