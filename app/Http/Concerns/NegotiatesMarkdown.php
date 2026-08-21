<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

trait NegotiatesMarkdown
{
    protected function prefersMarkdown(Request $request): bool
    {
        return $this->pickType($request, ['text/html', 'text/markdown']) === 'text/markdown';
    }

    /**
     * Pick the best representation from the given produces list, respecting
     * q-values, specificity, and q=0 rejections. Returns null when the client
     * accepts nothing we can serve.
     */
    protected function pickType(Request $request, array $produces): ?string
    {
        $accept = $request->header('Accept');
        if (! $accept) {
            return $produces[0] ?? null;
        }

        $entries = [];
        foreach (explode(',', $accept) as $raw) {
            $parts = array_map('trim', explode(';', trim($raw)));
            $type = strtolower(array_shift($parts));
            if ($type === '') {
                continue;
            }
            $q = 1.0;
            foreach ($parts as $param) {
                [$name, $value] = array_pad(array_map('trim', explode('=', $param, 2)), 2, '');
                if ($name === 'q' && is_numeric($value)) {
                    $q = max(0.0, min(1.0, (float) $value));
                }
            }
            $specificity = $type === '*/*' ? 0 : (str_ends_with($type, '/*') ? 1 : 2);
            $entries[] = compact('type', 'q', 'specificity');
        }

        $best = null;
        $bestQ = -1.0;
        $bestPosition = PHP_INT_MAX;
        foreach ($produces as $candidate) {
            $matched = null;
            $matchedPosition = PHP_INT_MAX;
            foreach ($entries as $i => $e) {
                $isMatch = $e['type'] === '*/*'
                    || (str_ends_with($e['type'], '/*') && str_starts_with($candidate, substr($e['type'], 0, -1)))
                    || $e['type'] === $candidate;
                if (! $isMatch) {
                    continue;
                }
                if ($matched === null
                    || $e['specificity'] > $matched['specificity']
                    || ($e['specificity'] === $matched['specificity'] && $i < $matchedPosition)
                ) {
                    $matched = $e;
                    $matchedPosition = $i;
                }
            }
            if ($matched === null) {
                continue;
            }
            if ($matched['q'] <= 0) {
                continue;
            }

            if ($matched['q'] > $bestQ
                || ($matched['q'] === $bestQ && $matchedPosition < $bestPosition)
            ) {
                $bestQ = $matched['q'];
                $bestPosition = $matchedPosition;
                $best = $candidate;
            }
        }

        return $best;
    }

    protected function withAcceptVary(Response $response): Response
    {
        $existing = $response->headers->get('Vary');
        $needed = ['Accept', 'Accept-Encoding'];
        if (! $existing) {
            $response->headers->set('Vary', implode(', ', $needed));

            return $response;
        }

        $tokens = array_map('trim', explode(',', $existing));
        $lower = array_map('strtolower', $tokens);
        foreach ($needed as $header) {
            if (! in_array(strtolower($header), $lower, true)) {
                $tokens[] = $header;
            }
        }
        $response->headers->set('Vary', implode(', ', $tokens));

        return $response;
    }

    protected function notAcceptableResponse(Request $request, array $produces): Response
    {
        $body = "This resource is available in:\n";
        foreach ($produces as $type) {
            $body .= '- ' . $type . "\n";
        }
        $body .= "\nYou requested: " . ($request->header('Accept') ?: '(none)') . "\n";

        $response = response($body, 406)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Cache-Control', 'no-store');

        return $this->withAcceptVary($response);
    }
}
