<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Poetry;
use App\Models\TopicCategory;
use App\Models\TopicCategoryDetail;
use Illuminate\Support\Facades\App;

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
                    'author_avatar' => ($poetPic) ? (str_starts_with($poetPic, 'http') ? $poetPic : '/' . $poetPic) : null,
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

        $topics = TopicCategory::with([
            'details' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            }
        ])
            ->whereHas('tags', function ($q) {
                // Check if any tag in this category is used in available poetry
                // fetching all used tags is expensive here, so let's use a whereExists or simple check logic
                // But simpler: Check if TopicCategory has any poetry linked via topic_category_id
                // OR via tags.
    
                // Optimisation: Just check if direct poetry exists for now, 
                // but previously `ExploreTopicController` filtered based on Tags.
                // Let's try to replicate `ExploreTopicController` logic roughly but simpler for sidebar.
                // Actually, `ExploreTopicController` filtered *tags*.
                // Here we want "Recommended Topics" (Categories).
                // Let's filter Categories that have ANY tags attached to poetry.
    
                // Since `poetry_tags` is JSON, doing a recursive check in SQL is hard.
                // Let's filter by checking if any poetry has topic_category_id set to this category.
                // This assumes `topic_category_id` is populated in Poetry table.
                // Step 152 in ExploreTopicController didn't use topic_category_id, it used tags.
    
                // Let's stick to categories that have tags which are used.
                // But simpler: let's just use `whereHas('poetry')` if that relationship exists.
                // If not, we fall back to generic `inRandomOrder` but maybe filtered by existence of tags?
                // "attached to to any poetry"
                // Let's try the direct relationship first.
                // Checking `Poetry` model in previous turns... `public function topicCategory() { return $this->belongsTo(TopicCategory::class, 'topic_category_id'); }`
                // So YES, we can check `whereHas('poetry')`.
    
            })
            // Actually, let's just check if there is poetry with this topic_category_id.
            ->whereHas('poetry', function ($q) {
                $q->where('visibility', 1);
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
}
