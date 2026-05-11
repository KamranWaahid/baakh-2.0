<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Poetry;
use App\Models\TopicCategory;
use App\Models\TopicCategoryDetail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class SidebarController extends Controller
{
    public function staffPicks(Request $request)
    {
        $lang = $request->header('Accept-Language', 'en');
        App::setLocale($lang);

        $monthMap = [
            'Jan' => 'جنوري',
            'Feb' => 'فيبروري',
            'Mar' => 'مارچ',
            'Apr' => 'اپريل',
            'May' => 'مئي',
            'Jun' => 'جون',
            'Jul' => 'جولاءِ',
            'Aug' => 'آگسٽ',
            'Sep' => 'سيپٽمبر',
            'Oct' => 'آڪٽوبر',
            'Nov' => 'نومبر',
            'Dec' => 'ڊسمبر',
        ];

        // Fetch featured poetry (where is_featured = 1)
        // Ensure distinct poets by uniqueing by poet_id, and randomize on each reload
        $picks = Poetry::where('is_featured', 1)
            ->where('visibility', 1)
            ->with(['translations', 'poet.all_details', 'category'])
            ->inRandomOrder()
            ->get()
            ->unique('poet_id') // Ensure 3 different poets
            ->take(3)
            ->values()
            ->map(function ($poetry) use ($lang, $monthMap) {
                // Determine title based on lang
                $title = '';
                $trans = $poetry->translations->where('lang', $lang)->first();
                if ($trans) {
                    $title = $trans->title;
                } else {
                    // Fallback to any title if specific lang missing
                    $title = $poetry->translations->first()->title ?? 'Untitled';
                }

                // Determine poet name (prefer laqab) from correct poet object
                $poet = $poetry->poet;
                if ($poet) {
                    $detail = $poet->all_details->where('lang', $lang)->first()
                        ?? $poet->all_details->first();

                    $poetName = $detail->poet_laqab ?? $detail->poet_name ?? 'Unknown Poet';
                    $poetPic = $poet->poet_pic;
                } else {
                    $poetName = 'Unknown Poet';
                    $poetPic = null;
                }

                $date = $poetry->created_at->format('M d');
                if ($lang === 'sd') {
                    foreach ($monthMap as $en => $sd) {
                        if (str_contains($date, $en)) {
                            $date = str_replace($en, $sd, $date);
                            break;
                        }
                    }
                }

                return [
                    'title' => $title,
                    'author' => $poetName,
                    'author_avatar' => $this->resolvePoetAvatar($poetPic),
                    'date' => $date,
                    'slug' => $poetry->poetry_slug,
                    'poet_slug' => $poet->poet_slug ?? '',
                    'cat_slug' => $poetry->category->slug ?? 'ghazal',
                ];
            });

        return response()->json($picks);
    }

    public function topics(Request $request)
    {
        $lang = $request->header('Accept-Language', 'en');

        // Fetch top topic categories with their details
        // Filter to only include topics that have at least one visible poetry attached
        // We assume 'topic_category_id' in Poetry table links to TopicCategory
        // OR we can check if any tags under this category are used.
        // Let's use the safer "tags used" approach or "direct link" approach depending on schema.
        // Based on ExploreTopicController, we linked categories -> tags -> poetry usage
        // But here we just show Categories.
        // Let's assume a Category is "used" if it has tags that are used in poetry_tags OR if poetry links to it directly.

        // 1. Get all unique tag IDs used in visible poetry
        // Logic copied from ExploreTopicController to ensure consistency
        $usedTagIds = Poetry::where('visibility', 1)
            ->whereNotNull('poetry_tags')
            ->pluck('poetry_tags')
            ->flatMap(function ($tagsJson) {
                $tags = json_decode($tagsJson, true);
                return is_array($tags) ? $tags : [];
            })
            ->unique()
            ->values()
            ->all();

        $topics = TopicCategory::with([
            'details' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            }
        ])
            // Filter Categories that have ANY tags which are used in poetry
            ->whereHas('tags', function ($q) use ($usedTagIds) {
                $q->whereIn('id', $usedTagIds);
            })
            ->inRandomOrder()
            ->take(12)
            ->get()
            ->map(function ($category) use ($lang) {
                $detail = $category->details->first();

                // Fallback to any detail if requested lang is missing
                if (!$detail) {
                    $detail = TopicCategoryDetail::where('topic_category_id', $category->id)->first();
                }

                return [
                    'name' => $detail->name ?? 'Unknown',
                    'slug' => $category->slug
                ];
            });

        return response()->json($topics);
    }

    private function resolvePoetAvatar(?string $avatar): ?string
    {
        if (!$avatar) {
            return null;
        }
        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        $relative = ltrim($avatar, '/');
        if ($relative === '') {
            return null;
        }
        if (File::exists(public_path($relative))) {
            return '/' . $relative;
        }

        $candidates = $this->avatarPathCandidates($relative);
        $resolvedCloudUrl = $this->resolveFirstReachableCloudUrl($relative, $candidates);
        if ($resolvedCloudUrl) {
            return $resolvedCloudUrl;
        }

        return null;
    }

    private function resolveFirstReachableCloudUrl(string $relative, array $candidates): ?string
    {
        $cloudBaseUrl = rtrim((string) config('filesystems.disks.s3.url', ''), '/');
        if ($cloudBaseUrl === '') {
            return null;
        }
        // Avoid blocking feed/sidebar API responses with remote HEAD probes.
        $orderedCandidates = array_values(array_unique(array_filter([
            $relative,
            ...$candidates,
        ])));
        if (empty($orderedCandidates)) {
            return null;
        }

        return $cloudBaseUrl . '/' . ltrim($orderedCandidates[0], '/');
    }

    private function avatarPathCandidates(string $relative): array
    {
        $relative = ltrim($relative, '/');
        $fileName = basename($relative);
        $dir = trim(dirname($relative), '.');
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);

        $legacyBase = preg_replace('/_[a-f0-9]{8,}_opt$/i', '', $baseName) ?? $baseName;
        $legacyBase = preg_replace('/_opt$/i', '', $legacyBase) ?? $legacyBase;

        $isOptimizedVariant = str_contains(strtolower($baseName), '_opt');

        $nameCandidates = array_values(array_unique([
            $isOptimizedVariant ? ($legacyBase . '_small.jpg') : $fileName,
            $fileName,
            $legacyBase . '_small.jpg',
            $legacyBase . '.jpg',
            $legacyBase . '.jpeg',
            $legacyBase . '.png',
            $legacyBase . '.webp',
        ]));

        $dirCandidates = array_values(array_unique(array_filter([
            $isOptimizedVariant ? 'Images' : null,
            $dir !== '' ? $dir : null,
            'assets/images/poets',
            'assets/Images/poets',
            'Images',
            'images',
        ])));

        $paths = [$relative];
        foreach ($dirCandidates as $dirCandidate) {
            foreach ($nameCandidates as $nameCandidate) {
                $paths[] = trim($dirCandidate, '/') . '/' . $nameCandidate;
            }
        }

        return array_values(array_unique($paths));
    }
}
