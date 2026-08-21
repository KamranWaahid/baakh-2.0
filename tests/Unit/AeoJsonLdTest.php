<?php

namespace Tests\Unit;

use App\Support\AeoJsonLd;
use Tests\TestCase;

class AeoJsonLdTest extends TestCase
{
    public function test_discovery_keywords_combine_poet_genre_and_topic(): void
    {
        $keys = AeoJsonLd::discoveryKeywords('Ishaq Samejo', 'Ghazal', ['Watan', 'Ishq']);

        $this->assertContains('Ishaq Samejo Ghazal', $keys);
        $this->assertContains('Sindhi Ghazal by Ishaq Samejo', $keys);
        $this->assertContains('Sindhi poetry on Watan', $keys);
        $this->assertContains('Sindhi Ghazal on Ishq', $keys);
        $this->assertContains('Ishaq Samejo Watan', $keys);
    }

    public function test_keywords_omit_empty_parts(): void
    {
        $keys = AeoJsonLd::discoveryKeywords('', 'Bait', []);

        $this->assertContains('Sindhi Bait', $keys);
        $this->assertSame($keys, array_values(array_unique($keys)));
    }

    public function test_place_uses_real_geo_parts_only(): void
    {
        $this->assertNull(AeoJsonLd::place(null, null, null));
        $place = AeoJsonLd::place('Radhan', 'Sindh', 'Pakistan');

        $this->assertSame('Place', $place['@type']);
        $this->assertSame('Radhan, Sindh, Pakistan', $place['name']);
        $this->assertSame('Radhan, Sindh, Pakistan', $place['address']);
    }

    public function test_iso_date_normalizes_and_rejects_empty(): void
    {
        $this->assertSame('1976-03-18', AeoJsonLd::isoDate('1976-03-18'));
        $this->assertNull(AeoJsonLd::isoDate(null));
        $this->assertNull(AeoJsonLd::isoDate(''));
    }

    public function test_birth_faq_is_omitted_without_date_or_place(): void
    {
        $this->assertNull(AeoJsonLd::birthQuestion('Ishaq Samejo', null, null, false));
    }

    public function test_birth_faq_uses_supplied_date_and_place(): void
    {
        $faq = AeoJsonLd::birthQuestion('Ishaq Samejo', '1976-03-18', 'Radhan, Sindh, Pakistan', false);

        $this->assertSame('Question', $faq['@type']);
        $this->assertStringContainsString('When was Ishaq Samejo born', $faq['name']);
        $this->assertStringContainsString('March 18, 1976', $faq['acceptedAnswer']['text']);
        $this->assertStringContainsString('Radhan, Sindh, Pakistan', $faq['acceptedAnswer']['text']);
    }

    public function test_about_things_skip_empty_names(): void
    {
        $things = AeoJsonLd::aboutThings([
            ['name' => 'Watan', 'alternateName' => 'وطن', 'url' => 'https://baakh.com/en/tag/watan'],
            ['name' => ''],
        ]);

        $this->assertCount(1, $things);
        $this->assertSame('Thing', $things[0]['@type']);
        $this->assertSame('Watan', $things[0]['name']);
        $this->assertSame('وطن', $things[0]['alternateName']);
    }

    public function test_json_id_list_parses_tag_id_arrays(): void
    {
        $this->assertSame([294, 292], AeoJsonLd::jsonIdList('["294","292"]'));
        $this->assertSame([1], AeoJsonLd::jsonIdList([1, 'x']));
        $this->assertSame([], AeoJsonLd::jsonIdList(null));
    }
}
