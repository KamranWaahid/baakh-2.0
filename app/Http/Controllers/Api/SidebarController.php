<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Poetry;
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
        // Order by latest or random, taking 3 items
        $picks = Poetry::where('is_featured', 1)
            ->with(['translations', 'poet_details', 'poet'])
            ->latest()
            ->take(3)
            ->get()
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

                // Determine poet name
                $poetName = $poetry->poet_details->poet_name ?? 'Unknown Poet';

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
                    'author_avatar' => $poetry->poet->author_avatar ?? null,
                    'date' => $date,
                    'slug' => $poetry->poetry_slug,
                    'poet_slug' => $poetry->poet->poet_slug ?? '',
                    'cat_slug' => $poetry->category->slug ?? 'ghazal', // default or fetch
                ];
            });

        return response()->json($picks);
    }

    public function topics(Request $request)
    {
        $lang = $request->header('Accept-Language', 'en');
        // Validate lang to ensure we don't query with invalid column values if data differs
        if (!in_array($lang, ['en', 'sd'])) {
            $lang = 'en';
        }

        // Valid Sindhi translations map for Roman tags
        $sindhiMap = [
            'watan' => 'وطن',
            'dharti' => 'ڌرتي',
            'dukh' => 'ڏک',
            'sindh' => 'سنڌ',
            'muhbat' => 'محبت',
            'husun' => 'حسن',
            'tasauf' => 'تصوف',
            'ishq' => 'عشق',
            'raat' => 'رات',
            'zindagi' => 'زندگي',
            'manzar' => 'منظر',
            'tareef' => 'تعريف',
            'piyaar' => 'پيار',
            'yaad' => 'ياد',
            'moat' => 'موت',
            'sufism' => 'تصوف',
            'poetry' => 'شاعري',
            'love' => 'پيار',
        ];

        $tags = Poetry::where('lang', $lang)
            ->whereNotNull('poetry_tags')
            ->pluck('poetry_tags')
            ->flatMap(function ($tagString) {
                // simple cleaning for ["tag"] or "tag, tag" formats
                $clean = str_replace(['[', ']', '"', '&quot;'], '', $tagString);
                return array_map(function ($t) {
                    return trim($t);
                }, explode(',', $clean));
            })
            ->filter(function ($tag) {
                return !empty($tag);
            })
            ->map(function ($tag) use ($lang, $sindhiMap) {
                // Translate only if lang is sd
                if ($lang === 'sd') {
                    $lower = strtolower($tag);
                    return $sindhiMap[$lower] ?? $tag;
                }
                return $tag;
            })
            ->countBy()
            ->sortDesc()
            ->take(15)
            ->keys()
            ->values();

        return response()->json($tags);
    }
}
