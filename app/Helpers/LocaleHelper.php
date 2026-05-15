<?php

/**
 * Parse Accept-Language (or a simple lang code) into a supported app locale.
 */
function resolve_request_locale(?string $acceptLanguage, string $default = 'sd'): string
{
    $raw = trim((string) $acceptLanguage);
    if ($raw === '') {
        return normalize_app_locale($default);
    }

    $primary = $raw;
    if (str_contains($primary, ',')) {
        $primary = explode(',', $primary, 2)[0];
    }

    $primary = trim($primary);
    if (str_contains($primary, ';')) {
        $primary = trim(explode(';', $primary, 2)[0]);
    }

    return normalize_app_locale($primary !== '' ? $primary : $default);
}

/**
 * Map arbitrary language tags to Baakh locales (en|sd).
 */
function normalize_app_locale(?string $locale): string
{
    $locale = strtolower(str_replace('_', '-', trim((string) $locale)));

    if ($locale === '' || $locale === '*') {
        return 'sd';
    }

    if ($locale === 'en' || str_starts_with($locale, 'en-')) {
        return 'en';
    }

    if ($locale === 'sd' || str_starts_with($locale, 'sd-') || str_starts_with($locale, 'snd')) {
        return 'sd';
    }

    return 'sd';
}
