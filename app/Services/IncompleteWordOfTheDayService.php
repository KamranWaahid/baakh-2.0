<?php

namespace App\Services;

use App\Models\DictionaryWordOfTheDay;
use App\Models\Lemma;
use App\Models\LemmaRelation;
use App\Models\Sense;
use App\Support\DictionaryText;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncompleteWordOfTheDayService
{
    public function __construct(
        protected DictionaryCompletionService $completion
    ) {
    }

    public function getTodayPayload(?int $userId = null): array
    {
        $today = Carbon::today();
        $progress = $this->progress();

        if ($progress['incomplete'] === 0) {
            return [
                'all_complete' => true,
                'message' => 'All dictionary words are complete.',
                'selection_date' => $today->toDateString(),
                'word' => null,
                'completion' => null,
                'progress' => $progress,
            ];
        }

        $assignment = $this->resolveTodayAssignment($today, $userId);

        if (!$assignment) {
            return [
                'all_complete' => true,
                'message' => 'All dictionary words are complete.',
                'selection_date' => $today->toDateString(),
                'word' => null,
                'completion' => null,
                'progress' => $this->progress(),
            ];
        }

        $lemma = $this->loadLemma($assignment->lemma_id);
        $checklist = $this->completion->evaluate($lemma);

        // If somehow completed outside this flow, advance immediately.
        if ($checklist['is_complete']) {
            $this->markAssignmentCompleted($assignment);
            $lemma->update([
                'completion_status' => Lemma::COMPLETION_COMPLETE,
                'completed_at' => now(),
                'completed_by' => $userId,
                'completion_score' => $checklist['score'],
                'checklist_json' => $checklist,
            ]);

            return $this->getTodayPayload($userId);
        }

        return [
            'all_complete' => false,
            'message' => null,
            'selection_date' => $today->toDateString(),
            'assignment_id' => $assignment->id,
            'word' => $this->serializeWord($lemma, $checklist),
            'completion' => $checklist,
            'progress' => $progress,
        ];
    }

    public function skipToday(?int $userId = null): array
    {
        $today = Carbon::today();
        $current = DictionaryWordOfTheDay::query()
            ->forDate($today)
            ->current()
            ->first();

        if ($current) {
            $current->update([
                'status' => DictionaryWordOfTheDay::STATUS_SKIPPED,
                'skipped_at' => now(),
            ]);
        }

        $next = $this->pickIncompleteLemma($today, $current?->lemma_id);
        if (!$next) {
            return $this->getTodayPayload($userId);
        }

        DictionaryWordOfTheDay::create([
            'selection_date' => $today->toDateString(),
            'lemma_id' => $next->id,
            'status' => DictionaryWordOfTheDay::STATUS_CURRENT,
            'priority_score' => $next->priority_score ?? null,
            'selected_by' => $userId,
        ]);

        return $this->getTodayPayload($userId);
    }

    /**
     * Save inline edits for the current word-of-the-day.
     */
    public function save(array $data, ?int $userId = null): array
    {
        $today = Carbon::today();
        $assignment = DictionaryWordOfTheDay::query()
            ->forDate($today)
            ->current()
            ->first();

        if (!$assignment) {
            throw ValidationException::withMessages([
                'word' => 'No incomplete word is assigned for today.',
            ]);
        }

        if (!empty($data['lemma_id']) && (int) $data['lemma_id'] !== (int) $assignment->lemma_id) {
            throw ValidationException::withMessages([
                'lemma_id' => 'The submitted word does not match today’s assignment.',
            ]);
        }

        $lemma = $this->loadLemma($assignment->lemma_id);

        DB::transaction(function () use ($lemma, $data, $userId) {
            $lemmaFields = collect($data)->only([
                'lemma',
                'normalized_lemma',
                'pos',
                'transliteration',
                'ipa',
                'phonetic',
                'pronunciation_simple',
                'audio_url',
                'syllabification',
                'etymology',
                'notes',
                'variants_reviewed',
                'examples_reviewed',
                'morphology_reviewed',
                'pronunciation_reviewed',
                'completion_notes',
            ])->filter(fn ($value) => $value !== null)->all();

            if (isset($lemmaFields['lemma']) && !array_key_exists('normalized_lemma', $lemmaFields)) {
                $lemmaFields['normalized_lemma'] = DictionaryText::normalizeForLookup($lemmaFields['lemma']);
            }

            if (!empty($lemmaFields)) {
                $lemma->update($lemmaFields);
            }

            $this->upsertPrimarySense($lemma, $data);
            $this->upsertExample($lemma, $data);
            $this->upsertRelations($lemma, $data);

            $lemma->refresh()->load(['senses.examples', 'morphology', 'variants', 'lemmaRelations']);
        });

        $lemma = $this->loadLemma($assignment->lemma_id);
        $checklist = $this->completion->evaluate($lemma);

        $lemma->update([
            'completion_score' => $checklist['score'],
            'checklist_json' => $checklist,
        ]);

        if ($checklist['is_complete']) {
            $lemma->update([
                'completion_status' => Lemma::COMPLETION_COMPLETE,
                'completed_at' => now(),
                'completed_by' => $userId,
            ]);
            $this->markAssignmentCompleted($assignment);

            $payload = $this->getTodayPayload($userId);
            $payload['just_completed'] = true;
            $payload['message'] = 'Word marked complete. Loading the next incomplete word.';

            return $payload;
        }

        // Still incomplete — keep the same word for the rest of the day.
        $lemma->update([
            'completion_status' => Lemma::COMPLETION_PENDING,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        return [
            'all_complete' => false,
            'just_completed' => false,
            'message' => 'Saved. Some required fields are still missing.',
            'selection_date' => $today->toDateString(),
            'assignment_id' => $assignment->id,
            'word' => $this->serializeWord($lemma, $checklist),
            'completion' => $checklist,
            'progress' => $this->progress(),
        ];
    }

    public function progress(): array
    {
        $total = Lemma::query()->count();
        $completed = Lemma::query()->complete()->count();
        $incomplete = Lemma::query()->pendingCompletion()->count();

        // Catch pending-status drift: treat evaluated-incomplete as incomplete count fallback.
        if ($total > 0 && $incomplete === 0 && $completed < $total) {
            $incomplete = max(0, $total - $completed);
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'incomplete' => $incomplete,
            'percent_complete' => $total > 0 ? (int) round(($completed / $total) * 100) : 100,
        ];
    }

    private function resolveTodayAssignment(Carbon $today, ?int $userId): ?DictionaryWordOfTheDay
    {
        $current = DictionaryWordOfTheDay::query()
            ->forDate($today)
            ->current()
            ->first();

        if ($current) {
            $lemma = Lemma::find($current->lemma_id);
            if (!$lemma) {
                $current->delete();
                $current = null;
            } elseif ($lemma->completion_status === Lemma::COMPLETION_COMPLETE) {
                $this->markAssignmentCompleted($current);
                $current = null;
            } else {
                return $current;
            }
        }

        $next = $this->pickIncompleteLemma($today);
        if (!$next) {
            return null;
        }

        return DictionaryWordOfTheDay::create([
            'selection_date' => $today->toDateString(),
            'lemma_id' => $next->id,
            'status' => DictionaryWordOfTheDay::STATUS_CURRENT,
            'priority_score' => $next->priority_score ?? null,
            'selected_by' => $userId,
        ]);
    }

    private function pickIncompleteLemma(Carbon $today, ?int $excludeLemmaId = null): ?Lemma
    {
        $skippedToday = DictionaryWordOfTheDay::query()
            ->forDate($today)
            ->where('status', DictionaryWordOfTheDay::STATUS_SKIPPED)
            ->pluck('lemma_id')
            ->all();

        $yesterdayIds = DictionaryWordOfTheDay::query()
            ->forDate($today->copy()->subDay())
            ->pluck('lemma_id')
            ->all();

        $recentlyCompleted = Lemma::query()
            ->complete()
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(14))
            ->pluck('id')
            ->all();

        $exclude = array_values(array_unique(array_filter(array_merge(
            $skippedToday,
            $yesterdayIds,
            $recentlyCompleted,
            $excludeLemmaId ? [$excludeLemmaId] : []
        ))));

        $query = Lemma::query()
            ->pendingCompletion()
            ->with(['senses.examples', 'morphology', 'variants'])
            ->when($exclude !== [], fn ($q) => $q->whereNotIn('id', $exclude))
            ->orderByRaw('COALESCE(completion_score, 0) asc')
            ->orderBy('id');

        $candidates = $query->limit(40)->get();

        if ($candidates->isEmpty()) {
            // Relax consecutive-day exclusion if the queue is exhausted.
            $candidates = Lemma::query()
                ->pendingCompletion()
                ->with(['senses.examples', 'morphology', 'variants'])
                ->when($skippedToday !== [], fn ($q) => $q->whereNotIn('id', $skippedToday))
                ->when($excludeLemmaId, fn ($q) => $q->where('id', '!=', $excludeLemmaId))
                ->orderByRaw('COALESCE(completion_score, 0) asc')
                ->orderBy('id')
                ->limit(40)
                ->get();
        }

        if ($candidates->isEmpty()) {
            return null;
        }

        $ranked = $candidates
            ->map(function (Lemma $lemma) {
                $checklist = $this->completion->evaluate($lemma);
                $lemma->setAttribute('priority_score', $this->importanceScore($checklist));
                $lemma->setAttribute('_checklist', $checklist);

                return $lemma;
            })
            ->filter(fn (Lemma $lemma) => !($lemma->_checklist['is_complete'] ?? false))
            ->sortByDesc('priority_score')
            ->values();

        return $ranked->first();
    }

    /**
     * Higher = more important missing fields (prefer these first).
     */
    private function importanceScore(array $checklist): int
    {
        $weights = [
            'has_headword' => 40,
            'has_curated_sense' => 35,
            'senses_have_definitions' => 35,
            'has_pos' => 25,
            'has_normalized_form' => 20,
            'senses_have_language_direction' => 15,
            'senses_have_source' => 15,
            'examples_reviewed' => 8,
            'variants_reviewed' => 5,
            'morphology_reviewed' => 5,
            'pronunciation_reviewed' => 5,
        ];

        $score = 0;
        foreach ($checklist['missing_requirements'] ?? [] as $missing) {
            $score += $weights[$missing['key']] ?? 3;
        }

        // Prefer lower completion scores as well.
        $score += max(0, 100 - (int) ($checklist['score'] ?? 0));

        return $score;
    }

    private function markAssignmentCompleted(DictionaryWordOfTheDay $assignment): void
    {
        $assignment->update([
            'status' => DictionaryWordOfTheDay::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    private function loadLemma(int $lemmaId): Lemma
    {
        return Lemma::with([
            'senses.examples',
            'morphology',
            'variants',
            'lemmaRelations',
        ])->findOrFail($lemmaId);
    }

    private function serializeWord(Lemma $lemma, array $checklist): array
    {
        $primarySense = $lemma->senses->first();
        $synonyms = $lemma->lemmaRelations
            ->where('relation_type', 'synonym')
            ->pluck('related_word')
            ->values()
            ->all();
        $antonyms = $lemma->lemmaRelations
            ->where('relation_type', 'antonym')
            ->pluck('related_word')
            ->values()
            ->all();

        $example = $primarySense?->examples?->first();

        return [
            'id' => $lemma->id,
            'public_id' => $lemma->public_id,
            'lemma' => $lemma->lemma,
            'normalized_lemma' => $lemma->normalized_lemma,
            'pos' => $lemma->pos,
            'transliteration' => $lemma->transliteration,
            'ipa' => $lemma->ipa,
            'phonetic' => $lemma->phonetic,
            'pronunciation_simple' => $lemma->pronunciation_simple,
            'audio_url' => $lemma->audio_url,
            'syllabification' => $lemma->syllabification,
            'etymology' => $lemma->etymology,
            'notes' => $lemma->notes,
            'completion_status' => $lemma->completion_status,
            'completion_score' => $checklist['score'] ?? $lemma->completion_score,
            'variants_reviewed' => (bool) $lemma->variants_reviewed,
            'examples_reviewed' => (bool) $lemma->examples_reviewed,
            'morphology_reviewed' => (bool) $lemma->morphology_reviewed,
            'pronunciation_reviewed' => (bool) $lemma->pronunciation_reviewed,
            'sense' => [
                'id' => $primarySense?->id,
                'definition' => $primarySense?->definition,
                'definition_en' => $primarySense?->definition_en,
                'definition_sd' => $primarySense?->definition_sd,
                'short_gloss' => $primarySense?->short_gloss,
                'full_definition' => $primarySense?->full_definition,
                'part_of_speech' => $primarySense?->part_of_speech,
                'language_direction' => $primarySense?->language_direction,
                'source' => $primarySense?->source ?? $primarySense?->source_dictionary,
                'source_dictionary' => $primarySense?->source_dictionary,
                'review_status' => $primarySense?->review_status,
                'status' => $primarySense?->status,
            ],
            'example' => [
                'id' => $example?->id,
                'sentence' => $example?->sentence,
                'translation' => $example?->translation,
            ],
            'synonyms' => $synonyms,
            'antonyms' => $antonyms,
            'missing_fields' => collect($checklist['missing_requirements'] ?? [])
                ->map(fn ($item) => [
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'message' => $item['message'],
                ])
                ->values()
                ->all(),
        ];
    }

    private function upsertPrimarySense(Lemma $lemma, array $data): void
    {
        $senseData = [
            'definition' => $data['definition'] ?? null,
            'definition_en' => $data['definition_en'] ?? null,
            'definition_sd' => $data['definition_sd'] ?? null,
            'short_gloss' => $data['short_gloss'] ?? null,
            'full_definition' => $data['full_definition'] ?? null,
            'language_direction' => $data['language_direction'] ?? null,
            'source' => $data['source'] ?? null,
            'source_dictionary' => $data['source_dictionary'] ?? null,
            'part_of_speech' => $data['sense_pos'] ?? ($data['pos'] ?? null),
            'review_status' => $data['review_status'] ?? null,
            'status' => $data['sense_status'] ?? null,
        ];

        $hasAny = collect($senseData)->contains(fn ($v) => filled($v));
        if (!$hasAny && empty($data['sense_id'])) {
            return;
        }

        $sense = null;
        if (!empty($data['sense_id'])) {
            $sense = Sense::where('lemma_id', $lemma->id)->where('id', $data['sense_id'])->first();
        }
        $sense ??= $lemma->senses()->orderBy('sense_order')->orderBy('id')->first();

        $payload = array_filter($senseData, fn ($v) => $v !== null);

        if (!$sense) {
            if (!filled($payload['definition'] ?? null)
                && !filled($payload['definition_en'] ?? null)
                && !filled($payload['definition_sd'] ?? null)
                && !filled($payload['short_gloss'] ?? null)
                && !filled($payload['full_definition'] ?? null)) {
                return;
            }

            $payload['lemma_id'] = $lemma->id;
            $payload['definition'] = $payload['definition']
                ?? $payload['full_definition']
                ?? $payload['short_gloss']
                ?? $payload['definition_sd']
                ?? $payload['definition_en']
                ?? '';
            $payload['language_direction'] = $payload['language_direction'] ?? 'sindhi';
            $payload['source'] = $payload['source'] ?? $payload['source_dictionary'] ?? 'admin-wotd';
            $payload['source_dictionary'] = $payload['source_dictionary'] ?? $payload['source'] ?? 'admin-wotd';
            $payload['status'] = $payload['status'] ?? 'approved';
            $payload['review_status'] = $payload['review_status'] ?? 'reviewed';
            $payload['sense_order'] = 1;

            Sense::create($payload);

            return;
        }

        // Filling definitions should also advance curation status when missing.
        if (!isset($payload['review_status']) && ($sense->review_status === null || $sense->review_status === 'unreviewed')) {
            $payload['review_status'] = 'reviewed';
        }
        if (!isset($payload['status']) && ($sense->status === null || $sense->status === 'pending')) {
            $payload['status'] = 'approved';
        }
        if (!isset($payload['language_direction']) && !filled($sense->language_direction)) {
            $payload['language_direction'] = 'sindhi';
        }
        if (!isset($payload['source']) && !filled($sense->source) && !filled($sense->source_dictionary)) {
            $payload['source'] = 'admin-wotd';
            $payload['source_dictionary'] = $payload['source_dictionary'] ?? 'admin-wotd';
        }

        if (!empty($payload)) {
            $sense->update($payload);
        }
    }

    private function upsertExample(Lemma $lemma, array $data): void
    {
        $sentence = trim((string) ($data['example_sentence'] ?? ''));
        $translation = trim((string) ($data['example_translation'] ?? ''));

        if ($sentence === '' && $translation === '') {
            return;
        }

        $sense = $lemma->senses()->orderBy('sense_order')->orderBy('id')->first();
        if (!$sense) {
            return;
        }

        $example = $sense->examples()->orderBy('id')->first();
        $payload = array_filter([
            'sentence' => $sentence !== '' ? $sentence : null,
            'translation' => $translation !== '' ? $translation : null,
            'source' => 'admin-wotd',
        ], fn ($v) => $v !== null);

        if ($example) {
            $example->update($payload);
        } else {
            if ($sentence === '') {
                return;
            }
            $sense->examples()->create($payload);
        }

        if (!$lemma->examples_reviewed) {
            $lemma->update(['examples_reviewed' => true]);
        }
    }

    private function upsertRelations(Lemma $lemma, array $data): void
    {
        foreach (['synonyms' => 'synonym', 'antonyms' => 'antonym'] as $field => $type) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $words = $this->parseWordList($data[$field]);
            LemmaRelation::where('lemma_id', $lemma->id)->where('relation_type', $type)->delete();

            foreach ($words as $word) {
                LemmaRelation::create([
                    'lemma_id' => $lemma->id,
                    'relation_type' => $type,
                    'related_word' => $word,
                    'source' => 'admin-wotd',
                ]);
            }
        }
    }

    private function parseWordList(mixed $value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[,،]+/u', (string) $value) ?: [];
        }

        return collect($parts)
            ->map(fn ($word) => trim((string) $word))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
