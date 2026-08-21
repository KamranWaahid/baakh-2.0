<?php

namespace Tests\Unit;

use App\Support\AeoPlatformFaq;
use Tests\TestCase;

class AeoPlatformFaqTest extends TestCase
{
    public function test_home_faqs_are_conversational_in_english_and_sindhi(): void
    {
        $en = AeoPlatformFaq::rows('home', 'en');
        $sd = AeoPlatformFaq::rows('home', 'sd');

        $this->assertGreaterThanOrEqual(4, count($en));
        $this->assertSame(count($en), count($sd));
        $this->assertStringStartsWith('Where can I find', $en[0]['q']);
        $this->assertStringContainsString('باک', $sd[0]['a']);
        $this->assertStringContainsString('Roman', $en[1]['q']);
    }

    public function test_schema_marks_page_language(): void
    {
        $schema = AeoPlatformFaq::schema('about', 'en');

        $this->assertSame('Question', $schema[0]['@type']);
        $this->assertSame('en', $schema[0]['inLanguage']);
        $this->assertStringContainsString('Kamran Wahid', $schema[0]['acceptedAnswer']['text']);
        $this->assertStringContainsString('roadmap', strtolower(AeoPlatformFaq::rows('about', 'en')[3]['a']));
    }

    public function test_html_uses_inverted_pyramid_h3(): void
    {
        $html = AeoPlatformFaq::html('help', 'en');

        $this->assertStringContainsString('<h3>How do I use the smart search command shortcut on Baakh.com?</h3>', $html);
        $this->assertStringContainsString('Ctrl+K', $html);
        $this->assertStringContainsString('<a href=', $html);
    }

    public function test_listing_pages_have_intent_faqs(): void
    {
        $this->assertNotEmpty(AeoPlatformFaq::rows('poetry', 'en'));
        $this->assertNotEmpty(AeoPlatformFaq::rows('poets', 'sd'));
        $this->assertNotEmpty(AeoPlatformFaq::rows('explore', 'en'));
        $this->assertStringContainsString('Watan', AeoPlatformFaq::rows('explore', 'en')[0]['q']);
        $this->assertEmpty(AeoPlatformFaq::rows('contact', 'en'));
    }

    public function test_prosody_faq_does_not_claim_a_finished_ml_product(): void
    {
        $a = AeoPlatformFaq::rows('prosody', 'en')[1]['a'];

        $this->assertStringContainsString('roadmap', strtolower($a));
        $this->assertStringNotContainsString('already analyzes every meter automatically', $a);
    }
}
