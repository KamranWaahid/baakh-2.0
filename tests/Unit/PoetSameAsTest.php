<?php

namespace Tests\Unit;

use App\Models\Poets;
use App\Support\PoetSameAs;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PoetSameAsTest extends TestCase
{
    public function test_empty_identities_emit_no_sameas(): void
    {
        $this->assertSame([], PoetSameAs::urlsFromArray([]));
        $this->assertSame([], PoetSameAs::urls(new Poets()));
    }

    public function test_usernames_and_authority_ids_expand_to_https_urls(): void
    {
        $urls = PoetSameAs::urlsFromArray([
            'wikipedia_url' => 'https://en.wikipedia.org/wiki/Ishaq_Samejo',
            'wikidata_id' => 'Q12345',
            'google_kgmid' => '/g/11g0wghzst',
            'website_url' => 'https://example.com/poet',
            'twitter' => '@ishaqsamejo',
            'facebook' => 'ishaqsamejo',
            'instagram' => 'ishaq.samejo',
        ]);

        $this->assertContains('https://en.wikipedia.org/wiki/Ishaq_Samejo', $urls);
        $this->assertContains('https://www.wikidata.org/wiki/Q12345', $urls);
        $this->assertContains('https://www.google.com/search?kgmid=/g/11g0wghzst', $urls);
        $this->assertContains('https://example.com/poet', $urls);
        $this->assertContains('https://x.com/ishaqsamejo', $urls);
        $this->assertContains('https://www.facebook.com/ishaqsamejo', $urls);
        $this->assertContains('https://www.instagram.com/ishaq.samejo', $urls);
    }

    public function test_does_not_invent_wikipedia_or_kg_from_slug(): void
    {
        $poet = new Poets(['poet_slug' => 'ishaq-samejo']);

        $this->assertSame([], PoetSameAs::urls($poet));
        $this->assertSame([], PoetSameAs::urlsFromArray(['wikipedia_url' => '', 'twitter' => '']));
    }

    public function test_sanitize_normalizes_and_rejects_guessed_wikipedia(): void
    {
        $clean = PoetSameAs::sanitize([
            'wikipedia_url' => 'https://en.wikipedia.org/wiki/Shaikh_Ayaz',
            'wikidata_id' => 'https://www.wikidata.org/wiki/Q42',
            'google_kgmid' => 'g/11g0wghzst',
            'twitter' => 'https://twitter.com/baakhconnect',
            'instagram' => '',
        ]);

        $this->assertSame('https://en.wikipedia.org/wiki/Shaikh_Ayaz', $clean['wikipedia_url']);
        $this->assertSame('Q42', $clean['wikidata_id']);
        $this->assertSame('/g/11g0wghzst', $clean['google_kgmid']);
        $this->assertSame('baakhconnect', $clean['twitter']);
        $this->assertArrayNotHasKey('instagram', $clean);

        $this->expectException(ValidationException::class);
        PoetSameAs::sanitize(['wikipedia_url' => 'https://example.com/not-wikipedia']);
    }
}
