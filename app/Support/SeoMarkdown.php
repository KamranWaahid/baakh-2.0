<?php

namespace App\Support;

/**
 * Turns crawlable SEO HTML fragments into Markdown for Accept: text/markdown.
 */
class SeoMarkdown
{
    public static function fromSeoData(array $seoData): string
    {
        $title = trim((string) ($seoData['h1'] ?? $seoData['title'] ?? 'Baakh'));
        $md = '# ' . self::escapeHeading($title) . "\n\n";

        $description = trim((string) ($seoData['description'] ?? ''));
        if ($description !== '') {
            $md .= $description . "\n\n";
        }

        $body = self::fromHtml((string) ($seoData['raw_text'] ?? ''));
        if ($body !== '') {
            $md .= $body . "\n\n";
        }

        $md .= "## Machine-readable\n\n";
        $md .= '- [llms.txt](' . url('/llms.txt') . "): Agent index for Baakh\n";
        $md .= '- [AI catalog](' . url('/.well-known/ai-catalog.json') . "): Agentic resource directory\n";
        $md .= '- [Sitemap](' . url('/sitemap.xml') . "): Crawlable URL list\n";

        return rtrim($md) . "\n";
    }

    public static function notFound(string $path = ''): string
    {
        $md = "# 404 Not Found\n\n";
        $md .= "This path is not a page on Baakh";
        if ($path !== '') {
            $md .= ' (`/' . ltrim($path, '/') . '`)';
        }
        $md .= ".\n\n";
        $md .= "Use these indexes instead:\n\n";
        $md .= '- [llms.txt](' . url('/llms.txt') . "): Agent-readable site index\n";
        $md .= '- [Sitemap](' . url('/sitemap.xml') . "): All public URLs\n";
        $md .= '- [Contact](' . url('/en/contact') . "): Email and postal address\n";
        $md .= '- [Home](' . url('/sd') . "): Sindhi archive home\n";
        $md .= '- [English home](' . url('/en') . "): English archive home\n";

        return $md;
    }

    public static function fromHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $wrapped = '<?xml encoding="UTF-8"><div id="baakh-md-root">' . $html . '</div>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        libxml_clear_errors();

        $root = $dom->getElementById('baakh-md-root') ?? $dom->documentElement;
        if (! $root) {
            return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return trim(self::walk($root));
    }

    private static function walk(\DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= self::nodeToMarkdown($child);
        }

        return $out;
    }

    private static function nodeToMarkdown(\DOMNode $node): string
    {
        if ($node instanceof \DOMText) {
            return preg_replace('/[ \t]+/', ' ', $node->textContent) ?? '';
        }
        if (! $node instanceof \DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        $inner = self::walk($node);

        return match ($tag) {
            'h1' => "\n\n# " . self::escapeHeading(trim($inner)) . "\n\n",
            'h2' => "\n\n## " . self::escapeHeading(trim($inner)) . "\n\n",
            'h3' => "\n\n### " . self::escapeHeading(trim($inner)) . "\n\n",
            'h4' => "\n\n#### " . self::escapeHeading(trim($inner)) . "\n\n",
            'p' => "\n\n" . trim($inner) . "\n\n",
            'br' => "\n",
            'strong', 'b' => '**' . trim($inner) . '**',
            'em', 'i' => '*' . trim($inner) . '*',
            'a' => self::link($node, $inner),
            'ul', 'ol' => "\n" . $inner . "\n",
            'li' => '- ' . trim($inner) . "\n",
            'nav', 'section', 'article', 'div', 'main', 'span' => $inner,
            default => $inner,
        };
    }

    private static function link(\DOMElement $node, string $inner): string
    {
        $href = trim($node->getAttribute('href'));
        $label = trim($inner) !== '' ? trim($inner) : $href;
        if ($href === '') {
            return $label;
        }
        if (! str_starts_with($href, 'http') && ! str_starts_with($href, '/')) {
            return $label;
        }

        return '[' . $label . '](' . $href . ')';
    }

    private static function escapeHeading(string $text): string
    {
        return str_replace(["\n", '#'], [' ', ''], $text);
    }
}
