<?php

namespace Tests\Feature;

use App\Models\DictionaryWordOfTheDay;
use App\Models\Lemma;
use App\Models\Sense;
use App\Models\User;
use App\Services\IncompleteWordOfTheDayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IncompleteWordOfTheDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_word_stays_stable_across_requests(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create();

        $lemmaA = $this->makeIncompleteLemma('الف', 1);
        $this->makeIncompleteLemma('ب', 2);

        $service = app(IncompleteWordOfTheDayService::class);
        $first = $service->getTodayPayload($user->id);
        $second = $service->getTodayPayload($user->id);

        $this->assertFalse($first['all_complete']);
        $this->assertSame($first['word']['id'], $second['word']['id']);
        $this->assertSame(1, DictionaryWordOfTheDay::query()->current()->count());
        $this->assertTrue(in_array($first['word']['id'], [$lemmaA->id, 2], true));
    }

    public function test_completing_word_loads_next_incomplete_word(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create();
        Carbon::setTestNow('2026-07-28 10:00:00');

        $firstLemma = $this->makeIncompleteLemma('هڪ', 10);
        $secondLemma = $this->makeIncompleteLemma('ٻه', 20);

        $service = app(IncompleteWordOfTheDayService::class);
        $today = $service->getTodayPayload($user->id);
        $currentId = $today['word']['id'];

        $saved = $service->save([
            'lemma_id' => $currentId,
            'pos' => 'noun',
            'normalized_lemma' => $today['word']['normalized_lemma'] ?: $today['word']['lemma'],
            'definition' => 'primary definition',
            'definition_en' => 'one',
            'language_direction' => 'sindhi',
            'source' => 'test',
            'source_dictionary' => 'test',
            'review_status' => 'reviewed',
            'sense_status' => 'approved',
            'variants_reviewed' => true,
            'examples_reviewed' => true,
            'morphology_reviewed' => true,
            'pronunciation_reviewed' => true,
        ], $user->id);

        $this->assertTrue($saved['just_completed']);
        $this->assertNotSame($currentId, $saved['word']['id'] ?? null);
        $this->assertSame(Lemma::COMPLETION_COMPLETE, Lemma::find($currentId)->completion_status);
        $this->assertContains($saved['word']['id'], [$firstLemma->id, $secondLemma->id]);
    }

    public function test_skip_keeps_word_incomplete_and_loads_another(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create();

        $this->makeIncompleteLemma('ٽي', 30);
        $this->makeIncompleteLemma('چار', 40);

        $service = app(IncompleteWordOfTheDayService::class);
        $before = $service->getTodayPayload($user->id);
        $skippedId = $before['word']['id'];

        $after = $service->skipToday($user->id);

        $this->assertNotSame($skippedId, $after['word']['id'] ?? null);
        $this->assertSame(Lemma::COMPLETION_PENDING, Lemma::find($skippedId)->completion_status);
        $this->assertTrue(
            DictionaryWordOfTheDay::query()
                ->where('lemma_id', $skippedId)
                ->where('status', DictionaryWordOfTheDay::STATUS_SKIPPED)
                ->exists()
        );
    }

    private function makeIncompleteLemma(string $word, int $id): Lemma
    {
        $lemma = Lemma::query()->create([
            'id' => $id,
            'lemma' => $word,
            'normalized_lemma' => $word,
            'pos' => null,
            'status' => 'approved',
            'completion_status' => Lemma::COMPLETION_PENDING,
            'completion_score' => 40,
        ]);

        Sense::query()->create([
            'lemma_id' => $lemma->id,
            'definition' => 'partial',
            'status' => 'pending',
            'review_status' => 'unreviewed',
            'language_direction' => null,
            'source' => null,
        ]);

        return $lemma;
    }
}
