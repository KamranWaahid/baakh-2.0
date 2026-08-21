<?php

namespace Tests\Unit;

use App\Support\SeoMarkdown;
use Tests\TestCase;

class SeoMarkdownTest extends TestCase
{
    public function test_converts_headings_and_links(): void
    {
        $html = '<h2>About</h2><p>Hello <a href="/en/poets">poets</a>.</p><ul><li>One</li></ul>';
        $md = SeoMarkdown::fromHtml($html);

        $this->assertStringContainsString('## About', $md);
        $this->assertStringContainsString('[poets](/en/poets)', $md);
        $this->assertStringContainsString('- One', $md);
    }

    public function test_not_found_markdown_lists_indexes(): void
    {
        $md = SeoMarkdown::notFound('missing-page');

        $this->assertStringContainsString('# 404 Not Found', $md);
        $this->assertStringContainsString('llms.txt', $md);
        $this->assertStringContainsString('sitemap.xml', $md);
        $this->assertStringContainsString('contact', $md);
        $this->assertStringContainsString('missing-page', $md);
    }
}
