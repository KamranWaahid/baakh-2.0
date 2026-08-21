<?php

namespace App\Support;

use App\Models\Poets;
use Illuminate\Validation\ValidationException;

/**
 * Expands stored poet identities (Wikipedia, Wikidata, KG mid, website, social
 * usernames) into schema.org Person.sameAs URLs. Never invents Wikipedia or
 * Knowledge Graph IDs from a poet slug.
 */
class PoetSameAs
{
    public const KEYS = [
        'wikipedia_url',
        'wikidata_id',
        'google_kgmid',
        'website_url',
        'twitter',
        'facebook',
        'instagram',
    ];

    /**
     * @return list<string>
     */
    public static function urls(?Poets $poet): array
    {
        if (!$poet) {
            return [];
        }

        $raw = $poet->identities ?? null;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        return self::urlsFromArray(is_array($raw) ? $raw : []);
    }

    /**
     * @param  array<string, mixed>  $identities
     * @return list<string>
     */
    public static function urlsFromArray(array $identities): array
    {
        $urls = [];

        $wikipedia = self::httpsUrl($identities['wikipedia_url'] ?? null);
        if ($wikipedia && self::hostEndsWith($wikipedia, 'wikipedia.org')) {
            $urls[] = $wikipedia;
        }

        $wikidata = self::wikidataUrl($identities['wikidata_id'] ?? null);
        if ($wikidata) {
            $urls[] = $wikidata;
        }

        $kg = self::googleKgUrl($identities['google_kgmid'] ?? null);
        if ($kg) {
            $urls[] = $kg;
        }

        $website = self::httpsUrl($identities['website_url'] ?? null);
        if ($website) {
            $urls[] = $website;
        }

        $twitter = self::socialUsername($identities['twitter'] ?? null, ['x.com', 'twitter.com', 'www.x.com', 'www.twitter.com']);
        if ($twitter) {
            $urls[] = 'https://x.com/' . $twitter;
        }

        $facebook = self::facebookUrl($identities['facebook'] ?? null);
        if ($facebook) {
            $urls[] = $facebook;
        }

        $instagram = self::socialUsername($identities['instagram'] ?? null, ['instagram.com', 'www.instagram.com'], true);
        if ($instagram) {
            $urls[] = 'https://www.instagram.com/' . $instagram;
        }

        return array_values(array_unique($urls));
    }

    /**
     * Normalize admin input for storage. Empty fields become omitted keys.
     * Invalid Wikipedia / website / KG / Wikidata values throw validation errors.
     *
     * @param  array<string, mixed>|null  $input
     * @return array<string, string>
     */
    public static function sanitize(?array $input): array
    {
        $input = is_array($input) ? $input : [];
        $errors = [];
        $out = [];

        $wikipedia = self::nullableString($input['wikipedia_url'] ?? null);
        if ($wikipedia !== null) {
            $url = self::httpsUrl($wikipedia);
            if (!$url || !self::hostEndsWith($url, 'wikipedia.org')) {
                $errors['identities.wikipedia_url'] = ['Use a full https Wikipedia URL, or leave blank.'];
            } else {
                $out['wikipedia_url'] = $url;
            }
        }

        $wikidataRaw = self::nullableString($input['wikidata_id'] ?? null);
        if ($wikidataRaw !== null) {
            $qid = self::wikidataQid($wikidataRaw);
            if (!$qid) {
                $errors['identities.wikidata_id'] = ['Use a Wikidata Q-id (e.g. Q12345) or Wikidata URL, or leave blank.'];
            } else {
                $out['wikidata_id'] = $qid;
            }
        }

        $kgRaw = self::nullableString($input['google_kgmid'] ?? null);
        if ($kgRaw !== null) {
            $mid = self::googleKgmid($kgRaw);
            if (!$mid) {
                $errors['identities.google_kgmid'] = ['Use a Google Knowledge Graph mid (e.g. /g/11g0wghzst), or leave blank.'];
            } else {
                $out['google_kgmid'] = $mid;
            }
        }

        $website = self::nullableString($input['website_url'] ?? null);
        if ($website !== null) {
            $url = self::httpsUrl($website);
            if (!$url) {
                $errors['identities.website_url'] = ['Use a full https URL, or leave blank.'];
            } else {
                $out['website_url'] = $url;
            }
        }

        $twitter = self::nullableString($input['twitter'] ?? null);
        if ($twitter !== null) {
            $handle = self::socialUsername($twitter, ['x.com', 'twitter.com', 'www.x.com', 'www.twitter.com']);
            if (!$handle) {
                $errors['identities.twitter'] = ['Use an X/Twitter username (without @), or leave blank.'];
            } else {
                $out['twitter'] = $handle;
            }
        }

        $facebook = self::nullableString($input['facebook'] ?? null);
        if ($facebook !== null) {
            $url = self::facebookUrl($facebook);
            if (!$url) {
                $errors['identities.facebook'] = ['Use a Facebook username or profile URL, or leave blank.'];
            } else {
                $path = ltrim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');
                $out['facebook'] = str_contains($path, '/') ? $url : $path;
            }
        }

        $instagram = self::nullableString($input['instagram'] ?? null);
        if ($instagram !== null) {
            $handle = self::socialUsername($instagram, ['instagram.com', 'www.instagram.com'], true);
            if (!$handle) {
                $errors['identities.instagram'] = ['Use an Instagram username (without @), or leave blank.'];
            } else {
                $out['instagram'] = $handle;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $out;
    }

    /**
     * Blank form values for admin create/edit.
     *
     * @return array<string, string>
     */
    public static function emptyForm(array $identities = []): array
    {
        $form = [];
        foreach (self::KEYS as $key) {
            $form[$key] = (string) ($identities[$key] ?? '');
        }

        return $form;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function httpsUrl(mixed $value): ?string
    {
        $value = self::nullableString($value);
        if ($value === null) {
            return null;
        }
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($value);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return 'https://' . $host . $path . $query;
    }

    private static function hostEndsWith(string $url, string $suffix): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));

        return $host === $suffix || str_ends_with($host, '.' . $suffix);
    }

    private static function wikidataQid(mixed $value): ?string
    {
        $value = self::nullableString($value);
        if ($value === null) {
            return null;
        }
        if (preg_match('#wikidata\.org/wiki/(Q\d+)#i', $value, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('#^Q\d+$#i', $value)) {
            return strtoupper($value);
        }

        return null;
    }

    private static function wikidataUrl(mixed $value): ?string
    {
        $qid = self::wikidataQid($value);

        return $qid ? 'https://www.wikidata.org/wiki/' . $qid : null;
    }

    private static function googleKgmid(mixed $value): ?string
    {
        $value = self::nullableString($value);
        if ($value === null) {
            return null;
        }
        if (preg_match('#(?:kgmid=)?(/?g/[a-z0-9]+)#i', $value, $m)) {
            $mid = $m[1];
            if ($mid[0] !== '/') {
                $mid = '/' . $mid;
            }

            return strtolower($mid);
        }

        return null;
    }

    private static function googleKgUrl(mixed $value): ?string
    {
        $mid = self::googleKgmid($value);

        return $mid ? 'https://www.google.com/search?kgmid=' . $mid : null;
    }

    /**
     * @param  list<string>  $hosts
     */
    private static function socialUsername(mixed $value, array $hosts, bool $allowDot = false): ?string
    {
        $value = self::nullableString($value);
        if ($value === null) {
            return null;
        }
        $value = ltrim($value, '@');

        $looksLikeUrl = preg_match('#^https?://#i', $value) || preg_match('#^(www\.)?(x|twitter|instagram)\.com/#i', $value);
        if ($looksLikeUrl) {
            $url = self::httpsUrl($value);
            if (!$url) {
                return self::plainHandle($value, $allowDot);
            }
            $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
            $allowed = false;
            foreach ($hosts as $allowedHost) {
                if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return null;
            }
            $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');
            $first = explode('/', $path)[0] ?? '';

            return self::plainHandle($first, $allowDot);
        }

        return self::plainHandle($value, $allowDot);
    }

    private static function facebookUrl(mixed $value): ?string
    {
        $value = self::nullableString($value);
        if ($value === null) {
            return null;
        }
        if (preg_match('#^https?://#i', $value) || str_contains(strtolower($value), 'facebook.com')) {
            $url = self::httpsUrl($value);
            if (!$url || !self::hostEndsWith($url, 'facebook.com')) {
                return null;
            }
            $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');
            if ($path === '') {
                return null;
            }

            return 'https://www.facebook.com/' . $path;
        }

        $handle = self::plainHandle($value, true);
        if (!$handle) {
            return null;
        }

        return 'https://www.facebook.com/' . $handle;
    }

    private static function plainHandle(string $value, bool $allowDot = false): ?string
    {
        $value = ltrim(trim($value), '@');
        $pattern = $allowDot ? '/^[A-Za-z0-9._]{1,64}$/' : '/^[A-Za-z0-9_]{1,64}$/';
        if (!preg_match($pattern, $value)) {
            return null;
        }

        return $value;
    }
}
