<?php

namespace App\Services;

use App\Models\Cities;
use App\Models\CityDetails;
use App\Models\Poets;
use App\Models\PoetsDetail;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Builds / applies editor-form JSON for admin poet edit (AI copy/paste flow).
 * Image / poet_pic is intentionally excluded.
 */
class PoetEditorJsonService
{
    private const REQUIRED_LANGS = ['sd', 'en'];

    public function build(Poets $poet): array
    {
        $poet->loadMissing([
            'all_details.birthCity.details',
            'all_details.deathCity.details',
        ]);

        $existing = $poet->all_details
            ->keyBy(fn (PoetsDetail $detail) => strtolower((string) $detail->lang));

        $details = [];

        // Always include both Sindhi and English slots so AI / editors can fill both.
        foreach (self::REQUIRED_LANGS as $lang) {
            $details[] = $this->serializeDetail($existing->get($lang), $lang);
        }

        // Keep any extra languages already stored (e.g. ur).
        foreach ($existing as $lang => $detail) {
            if (in_array($lang, self::REQUIRED_LANGS, true)) {
                continue;
            }
            $details[] = $this->serializeDetail($detail, $lang);
        }

        return [
            '_schema' => 'baakh.poet.editor_json.v1',
            '_instructions' => 'Edit poet profile for Baakh. Always include details for BOTH lang=sd and lang=en (and ur if needed). Keep id and detail ids when present. Do NOT include or change image/poet_pic/photo fields. Use Standard Sindhi Arabic script for sd fields; English for en. birth_place / death_place may be city id or city name via birth_place_name / death_place_name. Paste back via Input JSON → Submit & Rewrite.',
            'id' => $poet->id,
            'poet_slug' => $poet->poet_slug,
            'date_of_birth' => $poet->date_of_birth,
            'date_of_death' => $poet->date_of_death,
            'visibility' => (bool) $poet->visibility,
            'is_featured' => (bool) $poet->is_featured,
            'details' => $details,
        ];
    }

    private function serializeDetail(?PoetsDetail $detail, string $lang): array
    {
        $lang = strtolower($lang);

        return [
            'id' => $detail?->id,
            'lang' => $lang,
            'poet_name' => $detail?->poet_name,
            'poet_laqab' => $detail?->poet_laqab,
            'pen_name' => $detail?->pen_name,
            'tagline' => $detail?->tagline,
            'poet_bio' => $detail?->poet_bio,
            'birth_place' => $detail && $detail->birth_place !== null && $detail->birth_place !== ''
                ? (string) $detail->birth_place
                : null,
            'birth_place_name' => $this->cityLabel($detail?->birthCity, $lang),
            'death_place' => $detail && $detail->death_place !== null && $detail->death_place !== ''
                ? (string) $detail->death_place
                : null,
            'death_place_name' => $this->cityLabel($detail?->deathCity, $lang),
        ];
    }

    public function import(Poets $poet, array $payload): Poets
    {
        $payload = $this->normalizeForImport($payload);

        if (isset($payload['id']) && (int) $payload['id'] !== (int) $poet->id) {
            throw new InvalidArgumentException('JSON id does not match this poet.');
        }

        $details = $payload['details'] ?? null;
        if (!is_array($details) || $details === []) {
            throw new InvalidArgumentException('JSON must include a non-empty details array.');
        }

        $langsPresent = collect($details)
            ->filter(fn ($detail) => is_array($detail))
            ->map(fn ($detail) => strtolower(trim((string) ($detail['lang'] ?? ''))))
            ->filter()
            ->unique()
            ->values();

        foreach (self::REQUIRED_LANGS as $requiredLang) {
            if (!$langsPresent->contains($requiredLang)) {
                throw new InvalidArgumentException("JSON details must include lang={$requiredLang} (both sd and en are required).");
            }
        }

        DB::beginTransaction();
        try {
            $updates = [];
            if (array_key_exists('poet_slug', $payload)) {
                $slug = trim((string) ($payload['poet_slug'] ?? ''));
                if ($slug === '') {
                    throw new InvalidArgumentException('poet_slug cannot be empty.');
                }
                $updates['poet_slug'] = $slug;
            }
            if (array_key_exists('date_of_birth', $payload)) {
                $updates['date_of_birth'] = $this->nullableDate($payload['date_of_birth']);
            }
            if (array_key_exists('date_of_death', $payload)) {
                $updates['date_of_death'] = $this->nullableDate($payload['date_of_death']);
            }
            if (array_key_exists('visibility', $payload)) {
                $updates['visibility'] = filter_var($payload['visibility'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
            if (array_key_exists('is_featured', $payload)) {
                $updates['is_featured'] = filter_var($payload['is_featured'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            if ($updates !== []) {
                $poet->update($updates);
            }

            $poet->all_details()->forceDelete();

            foreach ($details as $detail) {
                if (!is_array($detail)) {
                    continue;
                }

                $lang = strtolower(trim((string) ($detail['lang'] ?? '')));
                if ($lang === '' || !in_array($lang, ['sd', 'en', 'ur'], true)) {
                    throw new InvalidArgumentException('Each detail needs lang of sd, en, or ur.');
                }

                $name = trim((string) ($detail['poet_name'] ?? ''));
                $laqab = trim((string) ($detail['poet_laqab'] ?? ''));
                if (mb_strlen($name) < 3) {
                    throw new InvalidArgumentException("poet_name must be at least 3 characters for lang={$lang}.");
                }
                if (mb_strlen($laqab) < 3) {
                    throw new InvalidArgumentException("poet_laqab must be at least 3 characters for lang={$lang}.");
                }

                $poet->all_details()->create([
                    'poet_name' => $name,
                    'poet_laqab' => $laqab,
                    'pen_name' => $this->nullableString($detail['pen_name'] ?? null),
                    'tagline' => $this->nullableString($detail['tagline'] ?? null),
                    'poet_bio' => $this->nullableString($detail['poet_bio'] ?? null),
                    'birth_place' => $this->resolveCityId(
                        $detail['birth_place'] ?? null,
                        $detail['birth_place_name'] ?? null
                    ),
                    'death_place' => $this->resolveCityId(
                        $detail['death_place'] ?? null,
                        $detail['death_place_name'] ?? null
                    ),
                    'lang' => $lang,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $poet->fresh([
            'all_details.birthCity.details',
            'all_details.deathCity.details',
        ]);
    }

    public function normalizeForImport(array $payload): array
    {
        unset(
            $payload['poet_pic'],
            $payload['poet_pic_url'],
            $payload['poet_pic_raw'],
            $payload['image'],
            $payload['photo'],
            $payload['avatar']
        );

        if (isset($payload['details']) && is_array($payload['details'])) {
            $payload['details'] = array_map(function ($detail) {
                if (!is_array($detail)) {
                    return $detail;
                }
                unset(
                    $detail['poet_pic'],
                    $detail['poet_pic_url'],
                    $detail['image'],
                    $detail['photo'],
                    $detail['avatar']
                );

                return $detail;
            }, $payload['details']);
        }

        return $payload;
    }

    private function cityLabel(?Cities $city, ?string $preferredLang = null): ?string
    {
        if (!$city) {
            return null;
        }

        $details = $city->details ?? collect();
        $preferred = $preferredLang
            ? $details->firstWhere('lang', $preferredLang)?->city_name
            : null;
        $sdName = $details->firstWhere('lang', 'sd')?->city_name;
        $enName = $details->firstWhere('lang', 'en')?->city_name;
        $anyName = $details->first()?->city_name;
        $label = $preferred ?: ($preferredLang === 'en'
            ? ($enName ?: $sdName ?: $anyName)
            : ($sdName ?: $enName ?: $anyName));

        return $label !== null && $label !== '' ? $label : null;
    }

    private function resolveCityId(mixed $place, mixed $placeName): ?int
    {
        if (is_numeric($place)) {
            $id = (int) $place;
            if ($id > 0 && Cities::query()->whereKey($id)->exists()) {
                return $id;
            }
        }

        $name = trim((string) ($placeName ?: (is_string($place) ? $place : '')));
        if ($name === '' || is_numeric($name)) {
            return null;
        }

        $match = CityDetails::query()
            ->where('city_name', $name)
            ->value('city_id');

        if ($match) {
            return (int) $match;
        }

        $match = CityDetails::query()
            ->where('city_name', 'like', $name)
            ->value('city_id');

        return $match ? (int) $match : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $text)) {
            throw new InvalidArgumentException("Invalid date format: {$text}. Use YYYY-MM-DD.");
        }

        return substr($text, 0, 10);
    }
}
