<?php

namespace App\Services;

use App\Models\MokhiiPageMeta;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\PoetsDetail;
use App\Models\SeoAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MokhiiAutoFixService
{
    /**
     * Run all auto-fix routines.
     */
    public function runAll(): array
    {
        $stats = [
            'meta_descriptions_generated' => 0,
            'canonicals_suggested' => 0,
            'duplicates_detected' => 0,
        ];

        $stats['meta_descriptions_generated'] = $this->generateMissingMeta();
        $stats['canonicals_suggested'] = $this->suggestCanonicals();
        $stats['duplicates_detected'] = $this->detectDuplicateTitles();

        return $stats;
    }

    /**
     * Generate meta descriptions from existing content for pages that lack them.
     * Never invents content — only extracts from existing text.
     */
    private function generateMissingMeta(): int
    {
        $count = 0;

        // Poets without meta
        $poets = Poets::select('id', 'poet_slug')
            ->get();

        foreach ($poets as $poet) {
            $detail = PoetsDetail::where('poet_id', $poet->id)->first();
            if (!$detail || !$detail->poet_bio)
                continue;

            $bio = strip_tags($detail->poet_bio);
            $metaDesc = Str::limit($bio, 155, '...');

            MokhiiPageMeta::where('entity_type', 'poet')
                ->where('entity_id', $poet->id)
                ->whereNull('suggested_meta')
                ->update(['suggested_meta' => $metaDesc]);

            $count++;
        }

        return $count;
    }

    /**
     * Suggest canonical URLs for pages that might have duplicates.
     */
    private function suggestCanonicals(): int
    {
        $count = 0;
        $baseUrl = rtrim(config('app.url'), '/');

        // For all pages, canonical should be the /sd version (primary language)
        $pages = MokhiiPageMeta::whereNull('canonical_url')->get();

        foreach ($pages as $page) {
            $url = $page->url;

            // If URL contains /en/, canonical should be /sd/ version
            if (str_contains($url, '/en/')) {
                $canonical = str_replace('/en/', '/sd/', $url);
            } else {
                $canonical = $url;
            }

            $page->update(['canonical_url' => $canonical]);
            $count++;
        }

        return $count;
    }

    /**
     * Detect duplicate titles across audits.
     */
    private function detectDuplicateTitles(): int
    {
        $duplicates = DB::table('seo_audits')
            ->select('meta_title', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('meta_title')
            ->where('meta_title', '!=', '')
            ->groupBy('meta_title')
            ->having('cnt', '>', 1)
            ->get();

        $count = 0;
        foreach ($duplicates as $dup) {
            // Add duplicate_title issue to all audits with this title
            $audits = SeoAudit::where('meta_title', $dup->meta_title)->get();
            foreach ($audits as $audit) {
                $issues = $audit->issues ?? [];
                if (!in_array('duplicate_title', $issues)) {
                    $issues[] = 'duplicate_title';
                    $audit->update(['issues' => $issues]);
                }
            }
            $count += $dup->cnt;
        }

        return $count;
    }
}
