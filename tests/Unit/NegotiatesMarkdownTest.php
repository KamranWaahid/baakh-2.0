<?php

namespace Tests\Unit;

use App\Http\Concerns\NegotiatesMarkdown;
use Illuminate\Http\Request;
use Tests\TestCase;

class NegotiatesMarkdownTest extends TestCase
{
    use NegotiatesMarkdown;

    /**
     * @dataProvider acceptVectors
     */
    public function test_pick_type_follows_acceptmarkdown_vectors(?string $accept, ?string $expected): void
    {
        $request = Request::create('/en', 'GET');
        if ($accept === null) {
            $request->headers->remove('Accept');
        } else {
            $request->headers->set('Accept', $accept);
        }

        $this->assertSame($expected, $this->pickType($request, ['text/html', 'text/markdown']));
    }

    public static function acceptVectors(): array
    {
        return [
            'markdown only' => ['text/markdown', 'text/markdown'],
            'markdown preferred' => ['text/markdown, text/html;q=0.8', 'text/markdown'],
            'html only' => ['text/html', 'text/html'],
            'markdown rejected' => ['text/markdown;q=0, text/html', 'text/html'],
            'markdown rejected only' => ['text/markdown;q=0', null],
            'missing accept' => [null, 'text/html'],
            'star star' => ['*/*', 'text/html'],
            'chrome-like' => [
                'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'text/html',
            ],
        ];
    }
}
