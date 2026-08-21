<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Builds schema.org Person / CreativeWork / FAQ fragments from resolved archive data.
 * Callers must pass real poet/poem/tag fields — this class never invents biography.
 */
class AeoJsonLd
{
    /**
     * @param  list<string>  $topics
     * @return list<string>
     */
    public static function discoveryKeywords(string $poetName, string $genre, array $topics = []): array
    {
        $poetName = trim($poetName);
        $genre = trim($genre);
        $out = [];

        if ($poetName !== '' && $genre !== '') {
            $out[] = $poetName . ' ' . $genre;
            $out[] = 'Sindhi ' . $genre . ' by ' . $poetName;
        } elseif ($poetName !== '') {
            $out[] = $poetName . ' poetry';
        }

        if ($genre !== '') {
            $out[] = 'Sindhi ' . $genre;
        }

        foreach ($topics as $topic) {
            $topic = trim((string) $topic);
            if ($topic === '') {
                continue;
            }
            $out[] = 'Sindhi poetry on ' . $topic;
            if ($genre !== '') {
                $out[] = 'Sindhi ' . $genre . ' on ' . $topic;
            }
            if ($poetName !== '') {
                $out[] = $poetName . ' ' . $topic;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function place(?string $city, ?string $province = null, ?string $country = null): ?array
    {
        $parts = array_values(array_filter([
            trim((string) $city),
            trim((string) $province),
            trim((string) $country),
        ], fn ($part) => $part !== ''));

        if ($parts === []) {
            return null;
        }

        $name = implode(', ', $parts);

        return [
            '@type' => 'Place',
            'name' => $name,
            'address' => $name,
        ];
    }

    public static function isoDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array{cityName?: ?string, provinceName?: ?string, countryName?: ?string}|null  $location
     */
    public static function formatPlaceLabel(?array $location): ?string
    {
        if (! is_array($location)) {
            return null;
        }

        $place = self::place(
            $location['cityName'] ?? null,
            $location['provinceName'] ?? null,
            $location['countryName'] ?? null
        );

        return $place['name'] ?? null;
    }

    /**
     * @return array{name: string, acceptedAnswer: array{text: string}}|null
     */
    public static function birthQuestion(string $poetName, ?string $isoDate, ?string $place, bool $isSd): ?array
    {
        $poetName = trim($poetName);
        $isoDate = $isoDate ? trim($isoDate) : null;
        $place = $place ? trim($place) : null;

        if ($poetName === '' || ($isoDate === null && $place === null)) {
            return null;
        }

        $readableDate = $isoDate ? self::readableDate($isoDate, $isSd) : null;

        if ($isSd) {
            $name = $isoDate && $place
                ? $poetName . ' ڪڏهن ۽ ڪٿي ڄائو؟'
                : ($isoDate ? $poetName . ' ڪڏهن ڄائو؟' : $poetName . ' ڪٿي ڄائو؟');
            $bits = [];
            if ($readableDate) {
                $bits[] = $poetName . ' ' . $readableDate . ' تي ڄائو';
            }
            if ($place) {
                $bits[] = $place . ' ۾';
            }
            $text = trim(implode(' ', $bits)) . '.';
        } else {
            $name = $isoDate && $place
                ? 'When was ' . $poetName . ' born, and where?'
                : ($isoDate ? 'When was ' . $poetName . ' born?' : 'What is the birthplace of ' . $poetName . '?');
            if ($readableDate && $place) {
                $text = $poetName . ' was born on ' . $readableDate . ' in ' . $place . '.';
            } elseif ($readableDate) {
                $text = $poetName . ' was born on ' . $readableDate . '.';
            } else {
                $text = $poetName . ' was born in ' . $place . '.';
            }
        }

        return [
            '@type' => 'Question',
            'name' => $name,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $text,
            ],
        ];
    }

    public static function readableDate(string $isoDate, bool $isSd): ?string
    {
        try {
            $date = Carbon::parse($isoDate);
        } catch (\Throwable) {
            return $isoDate !== '' ? $isoDate : null;
        }

        if ($isSd) {
            return $date->format('Y-m-d');
        }

        return $date->format('F j, Y');
    }

    /**
     * @param  list<array{name?: string, alternateName?: string, url?: string}>  $topics
     * @return list<array<string, mixed>>
     */
    public static function aboutThings(array $topics): array
    {
        $out = [];
        foreach ($topics as $topic) {
            $name = trim((string) ($topic['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $thing = [
                '@type' => 'Thing',
                'name' => $name,
            ];
            $alt = trim((string) ($topic['alternateName'] ?? ''));
            if ($alt !== '' && $alt !== $name) {
                $thing['alternateName'] = $alt;
            }
            $url = trim((string) ($topic['url'] ?? ''));
            if ($url !== '') {
                $thing['url'] = $url;
            }
            $out[] = $thing;
        }

        return $out;
    }

    /**
     * @param  list<string>  $ids
     * @return list<int>
     */
    public static function jsonIdList(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
