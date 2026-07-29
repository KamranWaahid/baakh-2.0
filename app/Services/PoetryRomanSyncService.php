<?php

namespace App\Services;

use App\Models\Couplets;
use App\Models\LughatLemma;
use App\Models\Poetry;
use App\Models\PoetryTranslations;

/**
 * Rebuild poetry / couplet English (roman) lines from Sindhi + Romanizer dictionary.
 */
class PoetryRomanSyncService
{
    public function __construct(private RomanizerService $romanizer)
    {
    }

    /**
     * After a Lughat lemma gets AI transliteration, refresh linked poetry EN side.
     *
     * @return array{synced: bool, poetry_id: ?int, couplet_id: ?int, en_couplets_updated: int, title_updated: bool}
     */
    public function syncFromLemma(LughatLemma $lemma): array
    {
        $empty = [
            'synced' => false,
            'poetry_id' => $lemma->poetry_id ? (int) $lemma->poetry_id : null,
            'couplet_id' => $lemma->couplet_id ? (int) $lemma->couplet_id : null,
            'en_couplets_updated' => 0,
            'title_updated' => false,
        ];

        if ($lemma->poetry_id) {
            $result = $this->syncPoetry((int) $lemma->poetry_id);

            return [
                'synced' => true,
                'poetry_id' => (int) $lemma->poetry_id,
                'couplet_id' => $lemma->couplet_id ? (int) $lemma->couplet_id : null,
                'en_couplets_updated' => $result['en_couplets_updated'],
                'title_updated' => $result['title_updated'],
            ];
        }

        if ($lemma->couplet_id) {
            $updated = $this->syncStandaloneCouplet((int) $lemma->couplet_id);

            return [
                'synced' => $updated,
                'poetry_id' => null,
                'couplet_id' => (int) $lemma->couplet_id,
                'en_couplets_updated' => $updated ? 1 : 0,
                'title_updated' => false,
            ];
        }

        return $empty;
    }

    /**
     * @return array{en_couplets_updated: int, title_updated: bool}
     */
    public function syncPoetry(int $poetryId): array
    {
        $poetry = Poetry::query()->find($poetryId);
        if (!$poetry) {
            return ['en_couplets_updated' => 0, 'title_updated' => false];
        }

        $sdCouplets = Couplets::query()
            ->where('poetry_id', $poetryId)
            ->where(function ($q) {
                $q->whereNull('lang')->orWhere('lang', 'sd')->orWhere('lang', 'snd');
            })
            ->orderBy('id')
            ->get();

        $updated = 0;
        $index = 0;

        foreach ($sdCouplets as $couplet) {
            $index++;
            $plain = $this->romanizer->plainCoupletText((string) $couplet->couplet_text);
            if ($plain === '') {
                continue;
            }

            $roman = $this->romanizer->transliterate($plain);
            $slug = $poetry->poetry_slug . '-roman-' . $index;

            Couplets::updateOrCreate(
                [
                    'poetry_id' => $poetryId,
                    'couplet_slug' => $slug,
                    'lang' => 'en',
                ],
                [
                    'poet_id' => $couplet->poet_id ?: $poetry->poet_id,
                    'couplet_text' => $roman,
                    'topic_category_id' => $couplet->topic_category_id,
                    'book_id' => $couplet->book_id ?? $poetry->book_id,
                    'page_start' => $couplet->page_start ?? $poetry->page_start,
                    'page_end' => $couplet->page_end ?? $poetry->page_end,
                ]
            );
            $updated++;
        }

        $titleUpdated = $this->syncPoetryTitle($poetry);

        return [
            'en_couplets_updated' => $updated,
            'title_updated' => $titleUpdated,
        ];
    }

    public function syncStandaloneCouplet(int $coupletId): bool
    {
        $couplet = Couplets::query()->find($coupletId);
        if (!$couplet) {
            return false;
        }

        $lang = strtolower((string) ($couplet->lang ?? 'sd'));
        if ($lang === 'en') {
            return false;
        }

        $plain = $this->romanizer->plainCoupletText((string) $couplet->couplet_text);
        if ($plain === '') {
            return false;
        }

        $roman = $this->romanizer->transliterate($plain);
        $romanSlug = str_ends_with($couplet->couplet_slug, '-roman')
            ? $couplet->couplet_slug
            : $couplet->couplet_slug . '-roman';

        Couplets::updateOrCreate(
            [
                'couplet_slug' => $romanSlug,
                'lang' => 'en',
            ],
            [
                'poetry_id' => $couplet->poetry_id ?? 0,
                'poet_id' => $couplet->poet_id,
                'couplet_text' => $roman,
                'topic_category_id' => $couplet->topic_category_id,
                'book_id' => $couplet->book_id,
                'page_start' => $couplet->page_start,
                'page_end' => $couplet->page_end,
                'couplet_tags' => $couplet->couplet_tags,
            ]
        );

        return true;
    }

    private function syncPoetryTitle(Poetry $poetry): bool
    {
        $sd = PoetryTranslations::query()
            ->where('poetry_id', $poetry->id)
            ->where(function ($q) {
                $q->whereNull('lang')->orWhere('lang', 'sd')->orWhere('lang', 'snd');
            })
            ->first();

        if (!$sd || trim((string) $sd->title) === '') {
            return false;
        }

        $romanTitle = $this->romanizer->transliterate(
            $this->romanizer->plainCoupletText((string) $sd->title)
        );

        PoetryTranslations::updateOrCreate(
            [
                'poetry_id' => $poetry->id,
                'lang' => 'en',
            ],
            [
                'title' => $romanTitle,
                'info' => $sd->info,
                'source' => $sd->source,
            ]
        );

        return true;
    }
}
