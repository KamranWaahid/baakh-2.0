<?php

namespace App\Http\Controllers;

use App\Services\LlmsTxtService;
use Symfony\Component\HttpFoundation\Response;


/**
 * Machine-readable discovery files Ora and other agents probe at well-known
 * and root paths. These must never fall through to the SPA HTML shell.
 * llms.txt variants are generated from the live archive (see LlmsTxtService).
 */
class WellKnownController extends Controller
{
    /**
     * @var list<string>
     */
    private const LISTINGS = [
        'poets', 'poetry', 'couplets', 'genre', 'period', 'explore',
        'prosody', 'about', 'contact', 'help', 'privacy', 'terms',
    ];

    public function llmsTxt(LlmsTxtService $llms): Response
    {
        return $this->markdownFile($llms->index(), $llms->lastModified());
    }

    public function docsLlms(LlmsTxtService $llms): Response
    {
        return $this->markdownFile($llms->docs(), $llms->lastModified());
    }

    public function apiLlms(LlmsTxtService $llms): Response
    {
        return $this->markdownFile($llms->api(), $llms->lastModified());
    }

    public function poetryMonthLlms(LlmsTxtService $llms, int $year, int $month): Response
    {
        $page = max(1, (int) request()->query('page', 1));

        return $this->markdownFile($llms->poetryByMonth($year, $month, $page), $llms->lastModified());
    }

    public function agentsMarkdown(): Response
    {
        return $this->markdownFromPublic('agents.md');
    }

    public function indexMarkdown(): Response
    {
        return $this->markdownFromPublic('index.md');
    }

    public function authMarkdown(): Response
    {
        return $this->markdownFromPublic('auth.md');
    }

    public function developersMarkdown(): Response
    {
        return $this->markdownFromPublic('developers.md');
    }

    public function apiMarkdown(): Response
    {
        return $this->markdownFromPublic('api.md');
    }

    public function skillMarkdown(): Response
    {
        return $this->markdownFromPublic('skill.md');
    }

    public function agentMarkdown(): Response
    {
        return $this->markdownFromPublic('agent.md');
    }

    public function developerMarkdown(): Response
    {
        return $this->markdownFromPublic('developer.md');
    }

    public function sectionLlms(string $lang, string $section): Response
    {
        $lang = $lang === 'en' ? 'en' : 'sd';
        $section = strtolower($section);
        abort_unless(in_array($section, self::LISTINGS, true), 404);

        $url = url("/{$lang}/{$section}");
        $md = '# Baakh /' . $lang . '/' . $section . "\n\n";
        $md .= "Section index for agents. Canonical HTML: {$url}\n\n";
        $md .= "## When to use this\n\n";
        $md .= "Use this section when browsing the `{$section}` area of the Sindhi poetry archive. Prefer `Accept: text/markdown` on `{$url}` or `{$url}?mode=agent`.\n\n";
        $md .= '- [This section](' . $url . ")\n";
        $md .= '- [llms.txt](' . url('/llms.txt') . ")\n";
        $md .= '- [Sitemap](' . url('/sitemap.xml') . ")\n";

        return $this->markdownFile($md);
    }

    public function aiCatalog(): Response
    {
        return $this->jsonFromPublic('.well-known/ai-catalog.json');
    }

    public function apiCatalog(): Response
    {
        return $this->jsonFromPublic('.well-known/api-catalog.json', 'application/linkset+json', [
            'Link' => '<https://www.rfc-editor.org/rfc/rfc9727>; rel="profile"',
        ]);
    }

    private function markdownFromPublic(string $relative): Response
    {
        $path = public_path($relative);
        abort_unless(is_file($path), 404);

        return $this->markdownFile((string) file_get_contents($path));
    }

    /**
     * @param  array<string, string>  $extraHeaders
     */
    private function jsonFromPublic(string $relative, string $contentType = 'application/json; charset=utf-8', array $extraHeaders = []): Response
    {
        $path = public_path($relative);
        abort_unless(is_file($path), 404);

        return response((string) file_get_contents($path), 200, $this->corsHeaders(array_merge([
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=3600',
        ], $extraHeaders)));
    }

    private function markdownFile(string $body, ?\DateTimeInterface $lastModified = null): Response
    {
        $headers = [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'public, max-age=21600',
        ];
        if ($lastModified) {
            $headers['Last-Modified'] = $lastModified->format(\DATE_RFC7231);
        }

        return response($body, 200, $this->corsHeaders($headers));
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function corsHeaders(array $headers): array
    {
        $headers['Access-Control-Allow-Origin'] = '*';
        $headers['Access-Control-Allow-Methods'] = 'GET, HEAD, OPTIONS';

        return $headers;
    }
}
