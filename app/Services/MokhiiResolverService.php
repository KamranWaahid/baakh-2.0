<?php

namespace App\Services;

use App\Models\Categories;
use App\Models\Couplets;
use App\Models\MokhiiPageMeta;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\PoetsDetail;
use App\Models\SeoAudit;
use App\Models\Tags;
use App\Models\TopicCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MokhiiResolverService
{
    private string $baseUrl;

    /** Issue → handler mapping */
    private const FIXABLE_ISSUES = [
        'missing_meta_description',
        'meta_description_too_long',
        'missing_title',
        'title_too_long',
        'duplicate_title',
        'missing_canonical',
        'missing_hreflang',
        'missing_schema',
        'missing_h1',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('app.url'), '/');
    }

    /**
     * Run the resolver on all audited pages.
     * Returns stats about what was fixed.
     */
    public function resolveAll(): array
    {
        $stats = [
            'total_issues' => 0,
            'issues_fixed' => 0,
            'pages_fixed' => 0,
            'fixes_by_type' => [],
        ];

        $audits = SeoAudit::whereNotNull('issues')
            ->orderBy('score')
            ->get();

        foreach ($audits as $audit) {
            $issues = $audit->issues ?? [];
            if (empty($issues))
                continue;

            $fixableIssues = array_intersect($issues, self::FIXABLE_ISSUES);
            $stats['total_issues'] += count($issues);

            if (empty($fixableIssues))
                continue;

            $fixes = $this->resolveForUrl($audit->url, $fixableIssues);
            if (!empty($fixes)) {
                $this->storeFixes($audit->url, $fixes);

                // Mark resolved issues
                $remainingIssues = array_diff($issues, array_keys($fixes));
                $audit->update([
                    'issues' => !empty($remainingIssues) ? array_values($remainingIssues) : null,
                ]);

                $stats['pages_fixed']++;
                $stats['issues_fixed'] += count($fixes);

                foreach ($fixes as $issueType => $fix) {
                    $stats['fixes_by_type'][$issueType] = ($stats['fixes_by_type'][$issueType] ?? 0) + 1;
                }
            }
        }

        return $stats;
    }

    /**
     * Generate fixes for a specific URL and its issues.
     */
    private function resolveForUrl(string $url, array $issues): array
    {
        $entity = $this->resolveEntity($url);
        $fixes = [];

        foreach ($issues as $issue) {
            $fix = match ($issue) {
                'missing_meta_description' => $this->fixMetaDescription($url, $entity),
                'meta_description_too_long' => $this->fixMetaDescriptionLength($url, $entity),
                'missing_title' => $this->fixTitle($url, $entity),
                'title_too_long' => $this->fixTitleLength($url, $entity),
                'duplicate_title' => $this->fixDuplicateTitle($url, $entity),
                'missing_canonical' => $this->fixCanonical($url),
                'missing_hreflang' => $this->fixHreflang($url),
                'missing_schema' => $this->fixSchema($url, $entity),
                'missing_h1' => $this->fixH1($url, $entity),
                default => null,
            };

            if ($fix !== null) {
                $fixes[$issue] = $fix;
            }
        }

        return $fixes;
    }

    // ─── Fix Methods ──────────────────────────────────

    private function fixMetaDescription(string $url, ?array $entity): ?array
    {
        $desc = $this->generateDescription($entity, $url);
        if (!$desc)
            return null;

        return ['meta_description' => Str::limit($desc, 155, '…')];
    }

    private function fixMetaDescriptionLength(string $url, ?array $entity): ?array
    {
        $desc = $this->generateDescription($entity, $url);
        if (!$desc)
            return null;

        return ['meta_description' => Str::limit($desc, 155, '…')];
    }

    private function fixTitle(string $url, ?array $entity): ?array
    {
        $title = $this->generateTitle($entity, $url);
        return $title ? ['title' => $title] : null;
    }

    private function fixTitleLength(string $url, ?array $entity): ?array
    {
        $title = $this->generateTitle($entity, $url);
        if (!$title)
            return null;

        return ['title' => Str::limit($title, 57, '… | باک')];
    }

    private function fixDuplicateTitle(string $url, ?array $entity): ?array
    {
        // Generate a unique title by including the entity name
        $title = $this->generateTitle($entity, $url);
        return $title ? ['title' => $title] : null;
    }

    private function fixCanonical(string $url): ?array
    {
        // Canonical should always be the /sd/ version
        $canonical = $url;
        if (str_contains($url, '/en/')) {
            $canonical = str_replace('/en/', '/sd/', $url);
        }

        return ['canonical' => $canonical];
    }

    private function fixHreflang(string $url): ?array
    {
        $sdUrl = str_contains($url, '/en/')
            ? str_replace('/en/', '/sd/', $url)
            : $url;
        $enUrl = str_contains($url, '/sd/')
            ? str_replace('/sd/', '/en/', $url)
            : preg_replace('#(https?://[^/]+)(.*)#', '$1/en$2', $url);

        return [
            'hreflang' => [
                ['lang' => 'sd', 'url' => $sdUrl],
                ['lang' => 'en', 'url' => $enUrl],
                ['lang' => 'x-default', 'url' => $sdUrl],
            ],
        ];
    }

    private function fixSchema(string $url, ?array $entity): ?array
    {
        if (!$entity) {
            return ['schema' => $this->defaultSchema($url)];
        }

        $schema = match ($entity['type']) {
            'poet' => $this->poetSchema($entity),
            'poetry' => $this->poetrySchema($entity),
            'category' => $this->categorySchema($entity, $url),
            'tag' => $this->tagSchema($entity, $url),
            'topic' => $this->topicSchema($entity, $url),
            'couplet' => $this->coupletSchema($entity, $url),
            default => $this->defaultSchema($url),
        };

        return ['schema' => $schema];
    }

    private function fixH1(string $url, ?array $entity): ?array
    {
        // Generate an appropriate H1 for the page type
        $h1 = match ($entity['type'] ?? 'page') {
            'poet' => $entity['model']->details?->poet_laqab
            ?? $entity['model']->details?->poet_name
            ?? $entity['model']->poet_slug,
            'poetry' => $entity['model']->poetry_title ?? $entity['model']->poetry_slug,
            'category' => $entity['model']->category_name ?? $entity['model']->slug,
            'tag' => $entity['model']->tag_name ?? $entity['model']->slug,
            'topic' => $entity['model']->name ?? $entity['model']->slug,
            'couplet' => 'بيت',
            default => $this->titleFromPath($url),
        };

        return ['h1' => $h1];
    }

    // ─── Content Generators ────────────────────────────

    private function generateDescription(?array $entity, string $url): ?string
    {
        if (!$entity) {
            return $this->descriptionFromPath($url);
        }

        return match ($entity['type']) {
            'poet' => $this->poetDescription($entity),
            'poetry' => $this->poetryDescription($entity),
            'category' => "باک تي {$entity['model']->category_name} جا شعر پڙھو ۽ لطف وٺو — سنڌي شاعري جو آرڪائيو",
            'tag' => "{$entity['model']->tag_name} — سنڌي ادب ۾ ھن موضوع تي شاعري، غزل ۽ نظم جو مجموعو. باک تي پڙھو",
            'topic' => "باک تي {$entity['model']->name} بابت شاعري — سنڌي ادب جو ڊجيٽل آرڪائيو",
            'couplet' => $this->coupletDescription($entity),
            default => $this->descriptionFromPath($url),
        };
    }

    private function poetDescription(array $entity): string
    {
        $model = $entity['model'];
        $detail = $model->details;

        if ($detail?->poet_bio) {
            return Str::limit(strip_tags($detail->poet_bio), 155, '…');
        }

        $name = $detail?->poet_laqab ?? $detail?->poet_name ?? $model->poet_slug;
        $poetryCount = Poetry::where('poet_id', $model->id)->count();

        return "{$name} — باک تي {$poetryCount} شعري تخليقون. سنڌي شاعري جو ڊجيٽل آرڪائيو";
    }

    private function poetryDescription(array $entity): string
    {
        $model = $entity['model'];

        if ($model->poetry_description) {
            return Str::limit(strip_tags($model->poetry_description), 155, '…');
        }

        // Use first few lines of the poetry text
        if ($model->poetry_text) {
            $lines = array_filter(explode("\n", strip_tags($model->poetry_text)));
            $firstLines = implode(' — ', array_slice($lines, 0, 2));
            return Str::limit($firstLines, 155, '…');
        }

        $title = $model->poetry_title ?? $model->poetry_slug;
        return "{$title} — باک تي پڙھو. سنڌي شاعري جو آرڪائيو";
    }

    private function coupletDescription(array $entity): string
    {
        $model = $entity['model'];

        if ($model->couplet_text) {
            return Str::limit(strip_tags($model->couplet_text), 155, '…');
        }

        return 'سنڌي بيت — باک تي پڙھو ۽ شيئر ڪريو. سنڌي شاعري جو ڊجيٽل آرڪائيو';
    }

    private function generateTitle(?array $entity, string $url): ?string
    {
        if (!$entity) {
            $pathTitle = $this->titleFromPath($url);
            return $pathTitle ? "{$pathTitle} | باک" : null;
        }

        $title = match ($entity['type']) {
            'poet' => ($entity['model']->details?->poet_laqab
                ?? $entity['model']->details?->poet_name
                ?? $entity['model']->poet_slug) . ' — شاعر',
            'poetry' => $entity['model']->poetry_title ?? $entity['model']->poetry_slug,
            'category' => $entity['model']->category_name ?? $entity['model']->slug,
            'tag' => $entity['model']->tag_name ?? $entity['model']->slug,
            'topic' => $entity['model']->name ?? $entity['model']->slug,
            'couplet' => 'بيت — ' . Str::limit(strip_tags($entity['model']->couplet_text ?? ''), 40),
            default => $this->titleFromPath($url),
        };

        return $title ? Str::limit("{$title} | باک", 60) : null;
    }

    // ─── Schema Generators ─────────────────────────────

    private function poetSchema(array $entity): array
    {
        $model = $entity['model'];
        $detail = $model->details;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $detail?->poet_laqab ?? $detail?->poet_name ?? $model->poet_slug,
            'alternateName' => $detail?->poet_name,
            'url' => "{$this->baseUrl}/sd/poet/{$model->poet_slug}",
            'description' => $detail?->poet_bio ? Str::limit(strip_tags($detail->poet_bio), 200) : null,
            'knowsAbout' => 'Sindhi Poetry',
            'sameAs' => [],
        ];
    }

    private function poetrySchema(array $entity): array
    {
        $model = $entity['model'];
        $poetName = $model->poet?->details?->poet_laqab ?? $model->poet?->poet_slug ?? 'Unknown';

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $model->poetry_title ?? $model->poetry_slug,
            'author' => ['@type' => 'Person', 'name' => $poetName],
            'inLanguage' => 'sd',
            'genre' => $model->category?->category_name ?? 'Poetry',
        ];
    }

    private function coupletSchema(array $entity, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Quotation',
            'text' => Str::limit(strip_tags($entity['model']->couplet_text ?? ''), 300),
            'inLanguage' => 'sd',
            'url' => $url,
        ];
    }

    private function categorySchema(array $entity, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $entity['model']->category_name ?? $entity['model']->slug,
            'url' => $url,
        ];
    }

    private function tagSchema(array $entity, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $entity['model']->tag_name ?? $entity['model']->slug,
            'url' => $url,
            'about' => $entity['model']->tag_name,
        ];
    }

    private function topicSchema(array $entity, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $entity['model']->name ?? $entity['model']->slug,
            'url' => $url,
        ];
    }

    private function defaultSchema(string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'url' => $url,
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'باک', 'url' => $this->baseUrl],
        ];
    }

    // ─── Storage ───────────────────────────────────────

    private function storeFixes(string $url, array $fixes): void
    {
        $meta = MokhiiPageMeta::firstOrCreate(
            ['url' => $url],
            ['entity_type' => 'page', 'computed_at' => now()]
        );

        $existingFixes = $meta->mokhii_fixes ?? [];
        $mergedFixes = array_merge($existingFixes, $fixes);

        $meta->update([
            'mokhii_fixes' => $mergedFixes,
            'computed_at' => now(),
        ]);
    }

    // ─── Entity Resolution ─────────────────────────────

    private function resolveEntity(string $url): ?array
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        // Strip language prefix
        $path = preg_replace('#^/(sd|en)/#', '/', $path);

        // /poet/{slug}
        if (preg_match('#^/poet/([^/]+)$#', $path, $m)) {
            $model = Poets::where('poet_slug', $m[1])->with('details')->first();
            return $model ? ['type' => 'poet', 'model' => $model] : null;
        }

        // /poet/{poet}/{category}/{poetry}
        if (preg_match('#^/poet/[^/]+/[^/]+/([^/]+)$#', $path, $m)) {
            $model = Poetry::where('poetry_slug', $m[1])->with(['poet.details', 'category'])->first();
            return $model ? ['type' => 'poetry', 'model' => $model] : null;
        }

        // /couplets/{slug}
        if (preg_match('#^/couplets/([^/]+)$#', $path, $m)) {
            $model = Couplets::where('couplet_slug', $m[1])->first();
            return $model ? ['type' => 'couplet', 'model' => $model] : null;
        }

        // /tag/{slug}
        if (preg_match('#^/tag/([^/]+)$#', $path, $m)) {
            $model = Tags::where('slug', $m[1])->first();
            return $model ? ['type' => 'tag', 'model' => $model] : null;
        }

        // /topic/{slug}
        if (preg_match('#^/topic/([^/]+)$#', $path, $m)) {
            $model = TopicCategory::where('slug', $m[1])->first();
            return $model ? ['type' => 'topic', 'model' => $model] : null;
        }

        // /{category_slug} (e.g. /ghazal)
        $slug = ltrim($path, '/');
        if ($slug && !str_contains($slug, '/')) {
            $model = Categories::where('slug', $slug)->first();
            if ($model)
                return ['type' => 'category', 'model' => $model];
        }

        return null;
    }

    // ─── URL Helpers ───────────────────────────────────

    private function titleFromPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $path = preg_replace('#^/(sd|en)/?#', '', $path);
        $path = trim($path, '/');

        if (empty($path))
            return 'باک — سنڌي شاعري جو آرڪائيو';

        $map = [
            'poets' => 'شاعر',
            'poetry' => 'شاعري',
            'couplets' => 'بيت',
            'genre' => 'صنف',
            'period' => 'دور',
            'prosody' => 'عروض',
            'explore' => 'ڳوليو',
            'about' => 'بابت',
            'privacy' => 'رازداري پاليسي',
            'terms' => 'شرطون',
            'help' => 'مدد',
            'status' => 'صورتحال',
        ];

        return $map[$path] ?? ucfirst(str_replace(['-', '_'], ' ', $path));
    }

    private function descriptionFromPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $path = preg_replace('#^/(sd|en)/?#', '', $path);
        $path = trim($path, '/');

        if (empty($path)) {
            return 'باک — سنڌي شاعري جو ڊجيٽل آرڪائيو. شاعر، غزل، نظم، بيت ۽ وڌيڪ پڙھو';
        }

        $map = [
            'poets' => 'سنڌي شاعرن جي فهرست — باک. مشهور ۽ نوان شاعر ڳوليو',
            'poetry' => 'سنڌي شاعري جو مجموعو — غزل، نظم، رباعي ۽ وڌيڪ. باک تي پڙھو',
            'couplets' => 'سنڌي بيت — باک تي شيئر ۽ محفوظ ڪريو',
            'explore' => 'سنڌي ادب جا موضوع ڳوليو — باک',
        ];

        return $map[$path] ?? null;
    }
}
