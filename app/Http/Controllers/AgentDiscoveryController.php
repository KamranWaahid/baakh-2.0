<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agent Skills discovery (https://github.com/cloudflare/agent-skills-discovery-rfc).
 */
class AgentDiscoveryController extends Controller
{
    private const SCHEMA = 'https://schemas.agentskills.io/discovery/0.2.0/schema.json';

    private const SKILL_NAME = 'baakh-archive';

    public function show(Request $request, ?string $path = null): Response
    {
        $normalized = trim((string) $path, '/');

        if ($normalized === '' || $normalized === 'index.md') {
            return $this->directoryIndex();
        }

        if ($normalized === 'index.json') {
            return $this->discoveryIndex();
        }

        if ($normalized === self::SKILL_NAME . '/SKILL.md' || $normalized === 'SKILL.md') {
            return $this->skillMarkdown();
        }

        abort(404);
    }

    private function directoryIndex(): Response
    {
        $path = public_path('.well-known/agent-skills/index.md');
        abort_unless(is_file($path), 404);

        return $this->markdownResponse((string) file_get_contents($path));
    }

    private function discoveryIndex(): Response
    {
        $skill = $this->skillFile();
        $body = $skill['body'];
        $meta = $this->frontmatter($body);

        $payload = [
            '$schema' => self::SCHEMA,
            'skills' => [
                [
                    'name' => $meta['name'],
                    'type' => 'skill-md',
                    'description' => $meta['description'],
                    'url' => url('/.well-known/agent-skills/' . self::SKILL_NAME . '/SKILL.md'),
                    'digest' => 'sha256:' . hash('sha256', $body),
                ],
            ],
        ];

        return response()
            ->json($payload, 200, $this->corsHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'Cache-Control' => 'public, max-age=3600',
            ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function skillMarkdown(): Response
    {
        return $this->markdownResponse($this->skillFile()['body']);
    }

    /**
     * @return array{path: string, body: string}
     */
    private function skillFile(): array
    {
        $path = public_path('.well-known/agent-skills/' . self::SKILL_NAME . '/SKILL.md');
        abort_unless(is_file($path), 404);

        return [
            'path' => $path,
            'body' => (string) file_get_contents($path),
        ];
    }

    /**
     * @return array{name: string, description: string}
     */
    private function frontmatter(string $body): array
    {
        $name = self::SKILL_NAME;
        $description = 'Use Baakh for Sindhi poetry in the baakh.com archive.';

        if (preg_match('/^---\s*\n(.*?)\n---/s', $body, $yaml)) {
            if (preg_match('/^name:\s*["\']?([a-z0-9-]+)["\']?\s*$/m', $yaml[1], $m)) {
                $name = $m[1];
            }
            if (preg_match('/^description:\s*"(.*?)"\s*$/sm', $yaml[1], $m)) {
                $description = trim($m[1]);
            } elseif (preg_match('/^description:\s*(.+)$/m', $yaml[1], $m)) {
                $description = trim($m[1], " \t'\"");
            }
        }

        return [
            'name' => $name,
            'description' => $description,
        ];
    }

    private function markdownResponse(string $body): Response
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
