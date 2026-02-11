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
                    'id' => $poetry->id,
                    'title' => $title,
                    'author' => $poetName,
                    'author_avatar' => $poetPic,
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
        // Randomize for fresh content on each reload
        $topics = TopicCategory::with([
            'details' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            }
        ])
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
