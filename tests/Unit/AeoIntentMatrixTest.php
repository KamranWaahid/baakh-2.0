<?php

namespace Tests\Unit;

use App\Support\AeoIntentMatrix;
use Tests\TestCase;

class AeoIntentMatrixTest extends TestCase
{
    public function test_work_lookup_uses_canonical_poet_genre_poem_url(): void
    {
        $tokens = AeoIntentMatrix::hydrate('en', [
            'poet' => ['slug' => 'shaikh-ayaz', 'label' => 'Shaikh Ayaz'],
            'genre' => ['slug' => 'waee', 'label' => 'Waee'],
            'work' => ['slug' => 'raat-ai-wai', 'label' => 'Raat Ai Wai'],
        ]);

        $this->assertStringContainsString('/en/poet/shaikh-ayaz/waee/raat-ai-wai', $tokens['work']['url']);
        $this->assertStringNotContainsString('/poetry/shaikh-ayaz', $tokens['work']['url']);

        $rows = AeoIntentMatrix::rows(AeoIntentMatrix::SCENARIO_WORK, $tokens, 'en');
        $this->assertNotEmpty($rows);
        $this->assertStringContainsString('Where can I find Raat Ai Wai by Shaikh Ayaz online?', $rows[0]['q']);
        $this->assertStringContainsString('/en/poet/shaikh-ayaz/waee/raat-ai-wai', $rows[0]['links'][0]['href']);
        $this->assertStringNotContainsString('lyrics.baakh.com', strtolower($rows[0]['a']));
    }

    public function test_intersect_requires_poet_genre_and_topic(): void
    {
        $partial = AeoIntentMatrix::hydrate('en', [
            'poet' => ['slug' => 'ishaq-samejo', 'label' => 'Ishaq Samejo'],
            'genre' => ['slug' => 'ghazal', 'label' => 'Ghazal'],
        ]);
        $this->assertSame([], AeoIntentMatrix::rows(AeoIntentMatrix::SCENARIO_INTERSECT, $partial, 'en'));

        $full = AeoIntentMatrix::hydrate('sd', [
            'poet' => ['slug' => 'ishaq-samejo', 'label' => 'اسحاق سميجو'],
            'genre' => ['slug' => 'ghazal', 'label' => 'غزل'],
            'topic' => ['slug' => 'watan', 'label' => 'وطن'],
        ]);
        $rows = AeoIntentMatrix::rows(AeoIntentMatrix::SCENARIO_INTERSECT, $full, 'sd');
        $this->assertNotEmpty($rows);
        $this->assertStringContainsString('وطن', $rows[0]['q']);
        $this->assertStringContainsString('/sd/poet/ishaq-samejo', $rows[0]['links'][0]['href']);
        $this->assertStringContainsString('/sd/tag/watan', $rows[0]['links'][1]['href']);
    }

    public function test_snippet_does_not_claim_a_file_download(): void
    {
        $tokens = AeoIntentMatrix::hydrate('en', [
            'genre' => ['slug' => 'bait', 'label' => 'Bait'],
            'topic' => ['slug' => 'muhabat', 'label' => 'Muhabat'],
        ]);
        $rows = AeoIntentMatrix::rows(AeoIntentMatrix::SCENARIO_SNIPPET, $tokens, 'en');

        $this->assertStringContainsString('Muhabat', $rows[0]['q']);
        $this->assertStringContainsString('no separate download', strtolower($rows[0]['a']));
        $this->assertStringContainsString('/en/couplets', $rows[0]['links'][0]['href']);
    }

    public function test_bibliographic_skips_invented_era_and_needs_poet(): void
    {
        $this->assertSame([], AeoIntentMatrix::rows(AeoIntentMatrix::SCENARIO_BIBLIO, AeoIntentMatrix::hydrate('en', []), 'en'));

        $tokens = AeoIntentMatrix::hydrate('en', [
            'poet' => ['slug' => 'ishaq-samejo', 'label' => 'Ishaq Samejo'],
            'topic' => ['slug' => 'watan', 'label' => 'Watan'],
            'book' => ['label' => 'Selected Poems'],
        ]);
        $rows = AeoIntentMatrix::rows(AeoIntentMatrix::SCENARIO_BIBLIO, $tokens, 'en');
        $questions = implode(' ', array_column($rows, 'q'));
        $answers = implode(' ', array_column($rows, 'a'));

        $this->assertStringContainsString('Watan', $questions);
        $this->assertStringContainsString('Selected Poems', $answers);
        $this->assertStringNotContainsString('historical era', strtolower($questions . $answers));
        $this->assertStringNotContainsString('most famous', strtolower($answers));
    }

    public function test_keywords_only_use_filled_tokens(): void
    {
        $keys = AeoIntentMatrix::keywords(AeoIntentMatrix::hydrate('en', [
            'poet' => ['slug' => 'shaikh-ayaz', 'label' => 'Shaikh Ayaz'],
            'genre' => ['slug' => 'waee', 'label' => 'Waee'],
            'topic' => ['slug' => 'muhabat', 'label' => 'Muhabat'],
            'work' => ['slug' => 'raat-ai-wai', 'label' => 'Raat Ai Wai'],
        ]));

        $this->assertContains('Raat Ai Wai by Shaikh Ayaz', $keys);
        $this->assertContains('Shaikh Ayaz Waee on Muhabat', $keys);
        $this->assertContains('Sindhi Waee on Muhabat', $keys);
        $this->assertSame($keys, array_values(array_unique($keys)));
    }

    public function test_merge_faqs_dedupes_and_caps(): void
    {
        $a = [['@type' => 'Question', 'name' => 'One', 'acceptedAnswer' => ['text' => 'a']]];
        $b = [
            ['@type' => 'Question', 'name' => 'One', 'acceptedAnswer' => ['text' => 'dup']],
            ['@type' => 'Question', 'name' => 'Two', 'acceptedAnswer' => ['text' => 'b']],
        ];
        $merged = AeoIntentMatrix::mergeFaqs($a, $b, 8);

        $this->assertCount(2, $merged);
        $this->assertSame('a', $merged[0]['acceptedAnswer']['text']);
    }
}
