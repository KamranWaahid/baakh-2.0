<?php

namespace App\Services;

use App\Models\Categories;
use App\Models\Couplets;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Builds /llms.txt (and section twins) from live archive data, like the sitemap.
 */
class LlmsTxtService
{
    public const CACHE_TTL = 21600;

    public const MAX_MONTHLY = 400;

    public function index(): string
    {
        return Cache::remember('llms:index', self::CACHE_TTL, fn () => $this->buildIndex());
    }

    public function docs(): string
    {
        return Cache::remember('llms:docs', self::CACHE_TTL, fn () => $this->buildDocs());
    }

    public function api(): string
    {
        return Cache::remember('llms:api', self::CACHE_TTL, fn () => $this->buildApi());
    }

    public function poetryByMonth(int $year, int $month, int $page = 1): string
    {
        $page = max(1, $page);
        $key = "llms:poetry:{$year}:{$month}:{$page}";

        return Cache::remember($key, self::CACHE_TTL, fn () => $this->buildPoetryMonth($year, $month, $page));
    }

    public function lastModified(): Carbon
    {
        return $this->snapshot()['updated_at'];
    }

    public function forget(?Poetry $poetry = null): void
    {
        Cache::forget('llms:index');
        Cache::forget('llms:docs');
        Cache::forget('llms:api');
        Cache::forget('llms:snapshot');

        $year = (int) ($poetry?->created_at?->year ?: date('Y'));
        $month = (int) ($poetry?->created_at?->month ?: date('n'));
        for ($page = 1; $page <= 20; $page++) {
            Cache::forget("llms:poetry:{$year}:{$month}:{$page}");
        }
    }

    /**
     * @return array{poets: int, poems: int, couplets: int, genres: int, tags: int, updated_at: Carbon}
     */
    private function snapshot(): array
    {
        return Cache::remember('llms:snapshot', 300, function () {
            try {
                $dates = array_filter([
                    Poets::query()->where('visibility', 1)->max('updated_at'),
                    Poetry::query()->where('visibility', 1)->max('updated_at'),
                    Couplets::query()->where('visibility', 1)->max('updated_at'),
                ]);

                return [
                    'poets' => (int) Poets::query()->where('visibility', 1)->count(),
                    'poems' => (int) Poetry::query()->where('visibility', 1)->count(),
                    'couplets' => (int) Couplets::query()->where('visibility', 1)->count(),
                    'genres' => (int) Categories::query()->count(),
                    'tags' => (int) Tags::query()->count(),
                    'updated_at' => $dates === []
                        ? now()
                        : Carbon::parse(max($dates)),
                ];
            } catch (Throwable) {
                return [
                    'poets' => 0,
                    'poems' => 0,
                    'couplets' => 0,
                    'genres' => 0,
                    'tags' => 0,
                    'updated_at' => now(),
                ];
            }
        });
    }

    private function buildIndex(): string
    {
        $base = $this->canonicalBase();
        $stats = $this->snapshot();
        $md = "# Baakh\n";
        $md .= "> Baakh is a bilingual digital archive of Sindhi poetry (Sindhi `sd` and English `en`), preserving classical and modern works, poet biographies, genres, couplets, and related language tools.\n\n";
        $md .= "Baakh is a non-profit, open-source literary archive, not a commercial publisher. Public page URLs are locale-prefixed (`/sd/...`, `/en/...`). The REST API under `/api/v1/` is JSON. Song lyrics live on a separate site (lyrics.baakh.com / Bol). Prefer Markdown via `Accept: text/markdown` on archive pages. Unknown paths return HTTP 404.\n\n";
        $md .= $this->freshnessBlock($stats);
        $md .= "## When to use this\n";
        $md .= "Use Baakh when you need to do one of these jobs:\n\n";
        $md .= "- Find a Sindhi poet by name or slug and open their archive profile\n";
        $md .= "- Fetch a specific ghazal, bait, waee, nazm, or couplet with original Sindhi and Roman (`sd-Latn`) text\n";
        $md .= "- Cite a permanent Baakh URL for a poet, poem, genre, tag, or topic\n";
        $md .= "- Look up a Sindhi poetic form or theme from the archive as the source of record\n\n";
        $md .= "Do not use Baakh for song lyrics (use [lyrics.baakh.com](https://lyrics.baakh.com)), commercial book sales, general news, or literature that is not Sindhi poetry.\n\n";
        $md .= "How an agent should call Baakh:\n\n";
        $md .= "1. Start from this file, [docs/llms.txt]({$base}/docs/llms.txt), [api/llms.txt]({$base}/api/llms.txt), or [sitemap.xml]({$base}/sitemap.xml); unknown paths return HTTP 404\n";
        $md .= "2. Send `Accept: text/markdown` on public pages when you want Markdown instead of HTML\n";
        $md .= "3. Canonical URLs: `{$base}/{lang}/poet/{poet-slug}` and `{$base}/{lang}/poet/{poet-slug}/{genre-slug}/{poem-slug}` (`lang` is `sd` or `en`)\n";
        $md .= "4. JSON: `GET /api/v1/poets/{slug}`, `GET /api/v1/poetry/{slug}`, `GET /api/v1/feed` (add `lang=sd|en` where supported)\n\n";
        $md .= "## Pages\n";
        $md .= "- [Sindhi home]({$base}/sd): Primary archive homepage\n";
        $md .= "- [English home]({$base}/en): English-language archive homepage\n";
        $md .= "- [About]({$base}/en/about): Mission, history, and how the archive is organized\n";
        $md .= "- [Contact]({$base}/en/contact): Email (support@baakh.com) and postal address\n";
        $md .= "- [Poets]({$base}/en/poets): Index of Sindhi poets\n";
        $md .= "- [Poetry]({$base}/en/poetry): Index of poems and ghazals\n";
        $md .= "- [Genres]({$base}/en/genre): Poetic forms (ghazal, bait, waee, nazm, …)\n";
        $md .= "- [Explore topics]({$base}/en/explore): Theme and topic hubs\n";
        $md .= "- [Help]({$base}/en/help): How to browse the archive\n";
        $md .= "- [Documentation index]({$base}/docs/llms.txt): Agent docs, skills, and catalogs\n";
        $md .= "- [API index]({$base}/api/llms.txt): Live JSON endpoints\n";
        $md .= "- [Sitemap]({$base}/sitemap.xml): Complete public URL list\n\n";
        $md .= "## Canonical URL patterns\n";
        $md .= "- Poet profile: `{$base}/{lang}/poet/{poet-slug}`\n";
        $md .= "- Poem: `{$base}/{lang}/poet/{poet-slug}/{genre-slug}/{poem-slug}`\n";
        $md .= "- Tag hub: `{$base}/{lang}/tag/{tag-slug}`\n";
        $md .= "- Topic hub: `{$base}/{lang}/topic/{topic-slug}`\n\n";
        $md .= $this->recentPoetryBlock($base);
        $md .= $this->monthlyIndexBlock($base);
        $md .= "## API\n";
        $md .= "- [API llms.txt]({$base}/api/llms.txt): Endpoint index with last-updated counts\n";
        $md .= "- [Health]({$base}/api/health): Service check\n";
        $md .= "- [Feed]({$base}/api/v1/feed): Homepage poetry feed (`lang=sd|en`)\n";
        $md .= "- [Poet]({$base}/api/v1/poets/{slug}): Poet JSON\n";
        $md .= "- [Poetry]({$base}/api/v1/poetry/{slug}): Poem JSON\n\n";
        $md .= "## Skills\n";
        $md .= "- [baakh-archive]({$base}/.well-known/agent-skills/baakh-archive/SKILL.md): Look up Sindhi poets and poems, cite canonical URLs, and negotiate Markdown\n";
        $md .= "- [Skill index]({$base}/.well-known/agent-skills/index.json): Agent Skills discovery index\n\n";
        $md .= "## Optional\n";
        $md .= "- [Source code](https://github.com/KamranWaahid/baakh-2.0): Open-source archive application\n";
        $md .= "- [Lyrics / Bol](https://lyrics.baakh.com): Song lyrics (separate product)\n";
        $md .= "- [Privacy]({$base}/en/privacy): Privacy policy\n";
        $md .= "- [Terms]({$base}/en/terms): Terms of use\n";

        return $md;
    }

    private function buildDocs(): string
    {
        $base = $this->canonicalBase();
        $stats = $this->snapshot();
        $md = "# Baakh documentation\n";
        $md .= "> Agent-facing docs for the open-source Sindhi poetry archive at baakh.com.\n\n";
        $md .= $this->freshnessBlock($stats);
        $md .= "## When to use this\n\n";
        $md .= "Use this file when you need documentation, catalogs, or skills rather than a poem URL. Prefer `Accept: text/markdown` on public pages.\n\n";
        $md .= "## Indexes\n";
        $md .= "- [llms.txt]({$base}/llms.txt): Site index with recent poems and monthly files\n";
        $md .= "- [API llms.txt]({$base}/api/llms.txt): JSON endpoints\n";
        $md .= "- [Sitemap]({$base}/sitemap.xml): Every public URL, split by month\n";
        $md .= "- [AI catalog]({$base}/.well-known/ai-catalog.json): Agentic resource directory\n";
        $md .= "- [API catalog]({$base}/.well-known/api-catalog): RFC 9727 linkset\n";
        $md .= "- [Skill: baakh-archive]({$base}/.well-known/agent-skills/baakh-archive/SKILL.md)\n";
        $md .= "- [Skill index]({$base}/.well-known/agent-skills/index.json)\n\n";
        $md .= "## Human docs\n";
        $md .= "- [About]({$base}/en/about)\n";
        $md .= "- [Help]({$base}/en/help)\n";
        $md .= "- [Contact]({$base}/en/contact)\n";
        $md .= "- [Privacy]({$base}/en/privacy)\n";
        $md .= "- [Terms]({$base}/en/terms)\n";
        $md .= "- [Developers]({$base}/developers.md)\n";
        $md .= "- [Auth]({$base}/auth.md)\n";
        $md .= "- [Source](https://github.com/KamranWaahid/baakh-2.0)\n\n";
        $md .= $this->monthlyIndexBlock($base);

        return $md;
    }

    private function buildApi(): string
    {
        $base = $this->canonicalBase();
        $stats = $this->snapshot();
        $md = "# Baakh API\n";
        $md .= "> Read-only JSON for the open-source Sindhi poetry archive. No API key for public poets, poems, or the homepage feed.\n\n";
        $md .= $this->freshnessBlock($stats);
        $md .= "## When to use this\n\n";
        $md .= "Use these endpoints when you already have a slug, or when you need structured JSON instead of HTML/Markdown. Unknown API paths return JSON errors.\n\n";
        $md .= "## Endpoints\n";
        $md .= "- [Health]({$base}/api/health): Service check\n";
        $md .= "- [Feed]({$base}/api/v1/feed?lang=sd): Homepage poetry feed (`lang=sd|en`)\n";
        $md .= "- [Poets]({$base}/api/v1/poets): Poet list\n";
        $md .= "- [Poet]({$base}/api/v1/poets/{slug}): Poet JSON\n";
        $md .= "- [Poet poetry]({$base}/api/v1/poets/{slug}/poetry)\n";
        $md .= "- [Poetry]({$base}/api/v1/poetry/{slug}): Poem JSON\n";
        $md .= "- [Categories]({$base}/api/v1/categories)\n";
        $md .= "- [Couplets]({$base}/api/v1/couplets)\n";
        $md .= "- [Search]({$base}/api/v1/search?q=)\n";
        $md .= "- [RFC 9727 catalog]({$base}/.well-known/api-catalog)\n\n";
        $md .= "## Related\n";
        $md .= "- [llms.txt]({$base}/llms.txt)\n";
        $md .= "- [docs/llms.txt]({$base}/docs/llms.txt)\n";
        $md .= "- [Sitemap]({$base}/sitemap.xml)\n";

        return $md;
    }

    private function buildPoetryMonth(int $year, int $month, int $page): string
    {
        $base = $this->canonicalBase();
        $offset = ($page - 1) * self::MAX_MONTHLY;
        try {
            $query = Poetry::query()
                ->where('visibility', 1)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month);
            $total = (int) $query->count();
        } catch (Throwable) {
            $query = null;
            $total = 0;
        }
        $pages = max(1, (int) ceil($total / self::MAX_MONTHLY));
        abort_if($month < 1 || $month > 12 || $year < 1990 || $year > ((int) date('Y')) + 1, 404);
        abort_if($page > $pages && $total > 0, 404);

        $items = $query
            ? (clone $query)
                ->select('id', 'poetry_slug', 'poetry_title', 'category_id', 'poet_id', 'created_at', 'updated_at')
                ->with([
                    'category:id,slug',
                    'poet:id,poet_slug',
                    'info:id,poetry_id,title,lang',
                ])
                ->orderByDesc('updated_at')
                ->skip($offset)
                ->take(self::MAX_MONTHLY)
                ->get()
            : collect();

        $label = sprintf('%04d-%02d', $year, $month);
        $md = "# Baakh poetry {$label}\n";
        $md .= "> Public poems added in {$label}. This file updates when the archive changes.\n\n";
        $md .= "Last updated: " . $this->snapshot()['updated_at']->toDateString() . "\n";
        $md .= "Poems this month: {$total}";
        if ($pages > 1) {
            $md .= " (page {$page} of {$pages})";
        }
        $md .= "\n\n";
        $md .= "- [Site index]({$base}/llms.txt)\n";
        $md .= "- [Monthly sitemap]({$base}/sitemap/poetry-{$year}-{$month}.xml)\n\n";
        $md .= "## Poems\n";

        if ($items->isEmpty()) {
            $md .= "No public poems for this month.\n";

            return $md;
        }

        foreach ($items as $item) {
            $cat = $item->category->slug ?? 'uncategorized';
            $poet = $item->poet->poet_slug ?? 'unknown';
            $title = trim((string) ($item->info?->title ?: $item->poetry_title ?: $item->poetry_slug));
            $date = $item->updated_at?->toDateString() ?: $label;
            $href = "{$base}/en/poet/{$poet}/{$cat}/{$item->poetry_slug}";
            $md .= $this->mdLink("{$title} ({$date})", $href) . "\n";
        }

        if ($page < $pages) {
            $md .= "\n- [Next page]({$base}/llms/poetry-{$year}-{$month}.txt?page=" . ($page + 1) . ")\n";
        }

        return $md;
    }

    /**
     * @param  array{poets: int, poems: int, couplets: int, genres: int, tags: int, updated_at: Carbon}  $stats
     */
    private function freshnessBlock(array $stats): string
    {
        $md = 'Last updated: ' . $stats['updated_at']->toDateString() . "\n";
        $md .= 'Archive snapshot: '
            . number_format($stats['poets']) . ' poets · '
            . number_format($stats['poems']) . ' poems · '
            . number_format($stats['couplets']) . " couplets\n\n";

        return $md;
    }

    private function recentPoetryBlock(string $base): string
    {
        try {
            $items = Poetry::query()
                ->where('visibility', 1)
                ->select('id', 'poetry_slug', 'poetry_title', 'category_id', 'poet_id', 'updated_at')
                ->with([
                    'category:id,slug',
                    'poet:id,poet_slug',
                    'info:id,poetry_id,title,lang',
                ])
                ->orderByDesc('updated_at')
                ->limit(12)
                ->get();
        } catch (Throwable) {
            $items = collect();
        }

        $md = "## Recently updated poetry\n";
        if ($items->isEmpty()) {
            $md .= "No public poems in the snapshot yet. Use [sitemap/poetry.xml]({$base}/sitemap/poetry.xml).\n\n";

            return $md;
        }

        foreach ($items as $item) {
            $cat = $item->category->slug ?? 'uncategorized';
            $poet = $item->poet->poet_slug ?? 'unknown';
            $title = trim((string) ($item->info?->title ?: $item->poetry_title ?: $item->poetry_slug));
            $date = $item->updated_at?->toDateString() ?? '';
            $href = "{$base}/en/poet/{$poet}/{$cat}/{$item->poetry_slug}";
            $md .= $this->mdLink(trim($title . ($date !== '' ? " ({$date})" : '')), $href) . "\n";
        }

        return $md . "\n";
    }

    private function monthlyIndexBlock(string $base): string
    {
        try {
            $driver = DB::connection()->getDriverName();
            $yearExpr = $driver === 'sqlite' ? "strftime('%Y', created_at)" : 'YEAR(created_at)';
            $monthExpr = $driver === 'sqlite' ? "strftime('%m', created_at)" : 'MONTH(created_at)';

            $months = Poetry::query()
                ->where('visibility', 1)
                ->selectRaw("{$yearExpr} as year, {$monthExpr} as month, COUNT(*) as total, MAX(updated_at) as last_mod")
                ->groupBy('year', 'month')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->limit(36)
                ->get();
        } catch (Throwable) {
            $months = collect();
        }

        $md = "## Poetry by month\n";
        if ($months->isEmpty()) {
            $md .= "Monthly files appear after public poems are published. Full URL list: [sitemap/poetry.xml]({$base}/sitemap/poetry.xml).\n\n";

            return $md;
        }

        foreach ($months as $row) {
            $year = (int) $row->year;
            $month = (int) $row->month;
            if ($year < 1990 || $month < 1 || $month > 12) {
                continue;
            }
            $label = sprintf('%04d-%02d', $year, $month);
            $last = $row->last_mod ? Carbon::parse($row->last_mod)->toDateString() : $label;
            $href = "{$base}/llms/poetry-{$year}-{$month}.txt";
            $md .= $this->mdLink("{$label} — {$row->total} poems, updated {$last}", $href) . "\n";
        }

        return $md . "\n";
    }

    private function mdLink(string $label, string $href): string
    {
        $label = str_replace(['[', ']', "\n"], ['\\[', '\\]', ' '], $label);

        return "- [{$label}]({$href})";
    }

    private function canonicalBase(): string
    {
        $url = rtrim((string) config('app.url'), '/');
        if ($url === '' || str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
            return 'https://baakh.com';
        }

        return $url;
    }
}
