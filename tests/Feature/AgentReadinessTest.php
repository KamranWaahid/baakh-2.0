<?php

namespace Tests\Feature;

use Tests\TestCase;

class AgentReadinessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_unknown_path_returns_http_404(): void
    {
        $response = $this->get('/some-path-that-does-not-exist');

        $response->assertStatus(404);
        $response->assertSee('llms.txt', false);
        $response->assertSee('sitemap.xml', false);
    }

    public function test_unknown_locale_path_returns_http_404(): void
    {
        $this->get('/en/this-page-does-not-exist-xyz')->assertStatus(404);
    }

    public function test_404_markdown_body_points_agents_at_indexes(): void
    {
        $response = $this->get('/some-path-that-does-not-exist', [
            'Accept' => 'text/markdown',
        ]);

        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $this->assertVaryIncludesAccept($response);
        $response->assertSee('# 404 Not Found', false);
        $response->assertSee('/llms.txt', false);
        $response->assertSee('/sitemap.xml', false);
    }

    public function test_homepage_html_has_heading_structure_and_enough_text(): void
    {
        $response = $this->get('/en');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertNotFalse(stripos($html, '<h1'));
        $this->assertNotFalse(stripos($html, '<h2'));
        $this->assertNotFalse(stripos($html, '<h3'));
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertGreaterThanOrEqual(500, mb_strlen(preg_replace('/\s+/', ' ', $text) ?? ''));
        $this->assertVaryIncludesAccept($response);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"sameAs"', $html);
        $this->assertStringContainsString('x.com/BaakhConnect', $html);
        $this->assertStringContainsString('"contactPoint"', $html);
        $this->assertStringContainsString('support@baakh.com', $html);
        $this->assertStringContainsString('"address"', $html);
        $this->assertStringContainsString('PostalAddress', $html);
        $this->assertStringContainsString('Karachi', $html);
        $this->assertStringContainsString('/en/contact', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('Where can I find a complete online archive of Sindhi poetry?', $html);
        $this->assertStringContainsString('<h3>', $html);
    }

    public function test_sindhi_homepage_faqs_use_sindhi_questions(): void
    {
        $response = $this->get('/sd');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('سنڌي شاعريءَ جو مڪمل آن لائن آرڪائيو ڪٿي ملندو؟', $html);
        $this->assertStringContainsString('"inLanguage":"sd"', $html);
    }

    public function test_listing_pages_emit_intent_faqs(): void
    {
        $poetry = $this->get('/en/poetry');
        $poetry->assertOk();
        $poetry->assertSee('Where can I find the best Sindhi love ghazals online?', false);
        $poetry->assertSee('FAQPage', false);

        $about = $this->get('/en/about');
        $about->assertOk();
        $about->assertSee('Who created the Baakh Sindhi poetry archive?', false);
        $about->assertSee('Kamran Wahid', false);

        $help = $this->get('/en/help');
        $help->assertOk();
        $help->assertSee('Ctrl+K', false);
    }

    public function test_homepage_serves_markdown_when_requested(): void
    {
        $response = $this->get('/en', [
            'Accept' => 'text/markdown',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $this->assertVaryIncludesAccept($response);
        $body = $response->getContent();
        $this->assertStringStartsWith('# ', $body);
        $this->assertStringContainsString('## About the archive', $body);
        $this->assertStringContainsString('llms.txt', $body);
        $this->assertStringNotContainsString('<html', $body);
    }

    public function test_unsupported_accept_returns_406(): void
    {
        $response = $this->get('/en', [
            'Accept' => 'application/pdf',
        ]);

        $response->assertStatus(406);
        $this->assertVaryIncludesAccept($response);
        $response->assertSee('text/html', false);
        $response->assertSee('text/markdown', false);
    }

    public function test_llms_txt_is_markdown_and_follows_spec(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $body = $response->getContent();
        $this->assertStringStartsWith('# Baakh', $body);
        $this->assertStringContainsString('> Baakh is a bilingual', $body);
        $this->assertStringContainsString('## Pages', $body);
        $this->assertStringContainsString('https://baakh.com/sitemap.xml', $body);
        $this->assertStringContainsString('## When to use this', $body);
        $this->assertStringContainsString('https://baakh.com/en/contact', $body);
        $this->assertStringContainsString('Accept: text/markdown', $body);
        $this->assertStringContainsString('/.well-known/agent-skills/', $body);
    }

    public function test_contact_page_is_crawlable_without_javascript(): void
    {
        $response = $this->get('/en/contact');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertNotFalse(stripos($html, '<h1'));
        $this->assertStringContainsString('support@baakh.com', $html);
        $this->assertStringContainsString('Karachi', $html);
        $this->assertStringContainsString('ContactPage', $html);
        $this->assertGreaterThanOrEqual(500, $this->visibleTextLength($html));
    }

    public function test_about_and_privacy_have_enough_crawlable_text(): void
    {
        foreach (['/en/about', '/en/privacy'] as $path) {
            $response = $this->get($path);
            $response->assertOk();
            $this->assertGreaterThanOrEqual(500, $this->visibleTextLength($response->getContent()));
        }
    }

    public function test_contact_serves_markdown_when_requested(): void
    {
        $response = $this->get('/en/contact', [
            'Accept' => 'text/markdown',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $this->assertVaryIncludesAccept($response);
        $this->assertStringContainsString('support@baakh.com', $response->getContent());
    }

    public function test_agent_skills_index_matches_skill_digest(): void
    {
        $skillPath = public_path('.well-known/agent-skills/baakh-archive/SKILL.md');
        $this->assertFileExists($skillPath);
        $body = (string) file_get_contents($skillPath);
        $this->assertStringContainsString('## When to use this', $body);

        $response = $this->get('/.well-known/agent-skills/index.json');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json; charset=utf-8');

        $json = $response->json();
        $this->assertSame('https://schemas.agentskills.io/discovery/0.2.0/schema.json', $json['$schema']);
        $this->assertSame('baakh-archive', $json['skills'][0]['name']);
        $this->assertSame('skill-md', $json['skills'][0]['type']);
        $this->assertStringContainsString('when you need', strtolower($json['skills'][0]['description']));
        $this->assertSame('sha256:' . hash('sha256', $body), $json['skills'][0]['digest']);

        $static = json_decode((string) file_get_contents(public_path('.well-known/agent-skills/index.json')), true);
        $this->assertSame($json['skills'][0]['digest'], $static['skills'][0]['digest']);
    }

    public function test_agent_skills_directory_has_when_to_use_guidance(): void
    {
        $response = $this->get('/.well-known/agent-skills');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $body = $response->getContent();
        $this->assertStringContainsString('## When to use this', $body);
        $this->assertStringContainsString('baakh-archive', $body);
    }

    public function test_agent_skill_markdown_is_served(): void
    {
        $response = $this->get('/.well-known/agent-skills/baakh-archive/SKILL.md');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $response->assertSee('## When to use this', false);
        $response->assertSee('name: baakh-archive', false);
    }

    public function test_unknown_agent_skill_returns_http_404(): void
    {
        $this->get('/.well-known/agent-skills/does-not-exist/SKILL.md')->assertStatus(404);
    }

    public function test_sitemap_pages_include_contact(): void
    {
        $response = $this->get('/sitemap/pages.xml');

        $response->assertOk();
        $response->assertSee('/sd/contact', false);
        $response->assertSee('/en/contact', false);
    }

    public function test_known_listing_is_not_404(): void
    {
        $this->get('/en/poets')->assertOk();
        $this->get('/en/contact')->assertOk();
        $this->get('/sd/contact')->assertOk();
    }

    public function test_ai_catalog_is_valid_json(): void
    {
        $response = $this->get('/.well-known/ai-catalog.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json; charset=utf-8');
        $json = $response->json();
        $this->assertSame('1.0', $json['specVersion']);
        $this->assertSame('baakh.com', $json['host']['identifier']);
        $this->assertNotEmpty($json['entries']);
        $this->assertSame('urn:air:baakh.com:archive:baakh-archive', $json['entries'][0]['identifier']);
        $this->assertArrayHasKey('url', $json['entries'][0]);
        $this->assertArrayNotHasKey('data', $json['entries'][0]);
    }

    public function test_api_catalog_is_rfc9727_linkset_json(): void
    {
        $response = $this->get('/.well-known/api-catalog', [
            'Accept' => 'application/linkset+json',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('application/linkset+json', (string) $response->headers->get('Content-Type'));
        $json = $response->json();
        $this->assertNotEmpty($json['linkset'][0]['item']);
        $this->assertStringContainsString('/api/v1/feed', $json['linkset'][0]['item'][0]['href']);
    }

    public function test_well_known_llms_txt_is_markdown_not_html(): void
    {
        foreach (['/llms.txt', '/.well-known/llms.txt', '/llms.md', '/agents.md', '/index.md', '/auth.md', '/developers.md', '/api.md'] as $path) {
            $response = $this->get($path);
            $response->assertOk();
            $this->assertStringContainsString('text/markdown', (string) $response->headers->get('Content-Type'));
            $body = $response->getContent();
            $this->assertStringStartsWith('# ', $body);
            $this->assertStringNotContainsString('<html', $body);
        }
    }

    public function test_section_llms_txt_and_agent_mode(): void
    {
        $section = $this->get('/en/poets/llms.txt');
        $section->assertOk();
        $section->assertSee('When to use this', false);
        $section->assertSee('/en/poets', false);

        $agent = $this->get('/en?mode=agent');
        $agent->assertOk();
        $this->assertStringContainsString('text/markdown', (string) $agent->headers->get('Content-Type'));
        $this->assertStringStartsWith('# ', $agent->getContent());
    }

    public function test_unknown_well_known_file_is_not_spa_html(): void
    {
        $response = $this->get('/.well-known/http-message-signatures-directory');

        $response->assertStatus(404);
        $this->assertStringNotContainsString('id="root"', $response->getContent());
    }

    public function test_homepage_link_headers_include_catalogs(): void
    {
        $response = $this->get('/en');
        $link = (string) $response->headers->get('Link');
        $this->assertStringContainsString('api-catalog', $link);
        $this->assertStringContainsString('ai-catalog.json', $link);
        $response->assertSee('rel="alternate" type="text/markdown"', false);
        $response->assertSee('/index.md', false);
        $response->assertSee('speakable', false);
    }

    private function visibleTextLength(string $html): int
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_strlen(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private function assertVaryIncludesAccept($response): void
    {
        $vary = strtolower((string) $response->headers->get('Vary'));
        $this->assertStringContainsString('accept', $vary);
    }
}
