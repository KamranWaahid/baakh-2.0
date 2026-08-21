<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;


/**
 * Machine-readable discovery files Ora and other agents probe at well-known
 * and root paths. These must never fall through to the SPA HTML shell.
 * On Vercel, the same files are also served statically from public/.
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

    public function llmsTxt(): Response
    {
        return $this->markdownFromPublic('llms.txt');
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

    private function markdownFile(string $body): Response
    {
        return response($body, 200, $this->corsHeaders([
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]));
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
