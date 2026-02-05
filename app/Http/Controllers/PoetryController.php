<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use App\Models\UserComments;
use App\Traits\BaakhLikedTrait;
use Illuminate\Support\Str;
use App\Traits\BaakhSeoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;


class PoetryController extends UserController
{
    use BaakhSeoTrait;
    public function __construct()
    {
        parent::__construct();

    }

    public function index()
    {

    }

    public function with_genre($slug)
    {

    }

    /**
     * Poetry With Slug
     * 
     */
    public function with_slug($category, $slug)
    {
        $poetryUrl = URL::localized(route('poetry.with-slug', ['category' => $category, 'slug' => $slug]));

        // site language
        $locale = app()->getLocale();

        // get poetry by URL 
        $poetry = Poetry::with([
            'info' => function ($query) use ($locale) {
                $query->where('lang', $locale)->take(1); // Load only one translation
            },
            'all_couplets' => function ($query) use ($locale) {
                $query->where('lang', $locale); // Load all couplets with the specified language
            },
            'category' => function ($query) use ($category) {
                $query->where('slug', $category)->take(1); // Load category with the specified language
            }
        ])
            ->where(['poetry_slug' => $slug, 'visibility' => 1])
            ->first();

        if (empty($poetry)) {
            abort(404);
        }

        // media
        $media_videos = Media::where(['lang' => $locale, 'media_type' => 'video', 'poetry_id' => $poetry->id])->get();
        $media_audios = Media::where(['lang' => $locale, 'media_type' => 'audio', 'poetry_id' => $poetry->id])->get();

        $poet_id = $poetry->poet_id;
        $poet_info = Poets::with([
            'details' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            }
        ])->where('id', $poet_id)->first();

        // Poetry Tags
        if (!is_null($poetry->poetry_tags) && $poetry->poetry_tags != 'null') {
            $decodeTags = json_decode($poetry->poetry_tags);
            $used_tags = Tags::whereIn('slug', $decodeTags)->where('lang', app()->getLocale())->pluck('tag', 'slug');
        } else {
            $used_tags = null;
        }


        $next_poetry = Poetry::with([
            'info' => function ($query) use ($locale) {
                $query->where('lang', $locale)->take(1); // Load only one translation
            },
            'category'
        ])
            ->where('poet_id', $poet_id)->where('id', '>', $poetry->id)
            ->orderBy('id', 'asc')->first();

        $previous_poetry = Poetry::with([
            'info' => function ($query) use ($locale) {
                $query->where('lang', $locale)->take(1); // Load only one translation
            },
            'category'
        ])
            ->where('poet_id', $poet_id)->where('id', '<', $poetry->id)
            ->orderBy('id', 'desc')->first();
        /**
         * Poetry Meta
         */
        $poet_detail = $poet_info->details;
        $title = $poet_detail->poet_laqab . ' | ' . $poetry->poetry_title;



        $this->SEO_Poetry($poetry, $category, $poet_info);
        $compact_views = [
            'poetry',
            'poet_info',
            'poet_detail',
            'media_videos',
            'media_audios',
            'used_tags',
            'next_poetry',
            'previous_poetry',
            'poetryUrl',
        ];

        return view('web.poetry.with-category', compact($compact_views));
    }

    /**
     * API Show Poem
     * Returns JSON response for the poetry detail page
     */
    public function apiShow(Request $request, $slug)
    {
        $locale = $request->get('lang', 'sd'); // Default to Sindhi if not specified

        // Get poetry by URL with language constraint
        $poetry = Poetry::with([
            'info' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
            'all_couplets' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            },
            'category'
        ])
            ->where('poetry_slug', $slug)
            ->where('visibility', 1)
            ->whereHas('info', function ($q) use ($locale) {
                $q->where('lang', $locale);
            })
            ->first();

        // Fallback: If not found in preferred language, try any language but prioritize preferred metadata
        if (!$poetry) {
            $poetry = Poetry::with([
                'info' => function ($query) use ($locale) {
                    $query->orderByRaw("CASE WHEN lang = ? THEN 0 ELSE 1 END", [$locale]);
                },
                'all_couplets' => function ($query) use ($locale) {
                    $query->orderByRaw("CASE WHEN lang = ? THEN 0 ELSE 1 END", [$locale]);
                },
                'category'
            ])
                ->where(['poetry_slug' => $slug, 'visibility' => 1])
                ->first();
        }

        if (!$poetry) {
            return response()->json(['message' => 'Poem not found'], 404);
        }

        // Poet Info
        $poet_id = $poetry->poet_id;
        $poet_info = Poets::with([
            'details' => function ($query) use ($locale) {
                $query->where('lang', $locale);
            }
        ])->where('id', $poet_id)->first();

        // Tags
        $used_tags = [];
        if (!is_null($poetry->poetry_tags) && $poetry->poetry_tags != 'null') {
            $decodeTags = json_decode($poetry->poetry_tags);
            if (is_array($decodeTags)) {
                $used_tags = Tags::whereIn('slug', $decodeTags)
                    ->where('lang', $locale)
                    ->select('id', 'tag', 'slug')
                    ->get();
            }
        }

        // Related / Navigate
        $next_poetry = Poetry::with([
            'info' => function ($q) use ($locale) {
                $q->where('lang', $locale);
            }
        ])
            ->where('poet_id', $poet_id)->where('id', '>', $poetry->id)
            ->where('visibility', 1)
            ->orderBy('id', 'asc')->first();

        $previous_poetry = Poetry::with([
            'info' => function ($q) use ($locale) {
                $q->where('lang', $locale);
            }
        ])
            ->where('poet_id', $poet_id)->where('id', '<', $poetry->id)
            ->where('visibility', 1)
            ->orderBy('id', 'desc')->first();

        // Format Response
        $data = [
            'id' => $poetry->id,
            'title' => $poetry->info?->title ?? $poetry->poetry_title, // Fallback
            'slug' => $poetry->poetry_slug,
            'content' => $poetry->all_couplets->map(function ($c) {
                return $c->couplet_text;
            }), // Array of couplets
            'content_style' => $poetry->content_style ?? 'center',
            'info' => $poetry->info?->info,
            'source' => $poetry->info?->source,
            'views' => $poetry->views,
            'likes' => $poetry->likes_count ?? 0,
            'date' => $poetry->created_at->format('M d, Y'),
            'date_diff' => $poetry->created_at->diffForHumans(),

            'poet' => [
                'id' => $poet_info->id ?? 0,
                'name' => optional($poet_info->details)->poet_laqab ?? 'Unknown',
                'tagline' => optional($poet_info->details)->tagline ?? '',
                'bio' => strip_tags(optional($poet_info->details)->poet_bio ?? ''),
                'slug' => $poet_info->poet_slug ?? '',
                'avatar' => $poet_info->photo ? (str_starts_with($poet_info->photo, 'http') ? $poet_info->photo : '/' . $poet_info->photo) : null,
                'followers' => '2.3K',
            ],

            'category' => [
                'id' => $poetry->category_id,
                'name' => optional($poetry->category?->detail)->cat_name ?? $poetry->category?->slug ?? 'General',
                'slug' => $poetry->category?->slug
            ],

            'tags' => $used_tags,

            'navigation' => [
                'next' => $next_poetry ? [
                    'title' => $next_poetry->info?->title,
                    'slug' => $next_poetry->poetry_slug
                ] : null,
                'prev' => $previous_poetry ? [
                    'title' => $previous_poetry->info?->title,
                    'slug' => $previous_poetry->poetry_slug
                ] : null,
            ],

            'more_from_author' => Poetry::with([
                'info' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                },
                'category',
                'poet',
                'couplets'
            ])
                ->where('poet_id', $poet_id)
                ->where('id', '!=', $poetry->id)
                ->where('visibility', 1)
                ->latest()
                ->take(4)
                ->get()
                ->map(function ($p) {
                    return [
                        'title' => $p->info?->title ?? $p->poetry_title,
                        'slug' => $p->poetry_slug,
                        'poet_slug' => $p->poet->poet_slug ?? '',
                        'cat_slug' => $p->category->slug ?? 'ghazal',
                        'date' => $p->created_at->format('M d'),
                        'excerpt' => Str::limit($p->couplets->couplet_text ?? '', 80),
                        'claps' => '100', // Mock
                        'comments' => 5 // Mock
                    ];
                }),

            'recommended' => Poetry::with([
                'info' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                },
                'poet.details',
                'category',
                'couplets'
            ])
                ->where('id', '!=', $poetry->id)
                // simple random recommendation for now
                ->where('visibility', 1)
                ->inRandomOrder()
                ->take(4)
                ->get()
                ->map(function ($p) use ($locale) {
                    return [
                        'title' => $p->info?->title ?? $p->poetry_title,
                        'slug' => $p->poetry_slug,
                        'poet_slug' => $p->poet->poet_slug ?? '',
                        'cat_slug' => $p->category->slug ?? 'ghazal',
                        'author' => $p->poet?->details->where('lang', $locale)->first()?->poet_laqab ?? $p->poet?->poet_slug ?? 'Unknown',
                        'date' => optional($p->created_at)->format('M d') ?? '',
                        'excerpt' => Str::limit($p->couplets->first()?->couplet_text ?? '', 80),
                        'claps' => '200',
                        'comments' => 10
                    ];
                })
        ];

        return response()->json($data);
    }

    /**
     * getRelatedPoetry contains related poetry to the opened poetry
     * it accepts php array of tags and current opened poetry id
     * it will check those tags into Poetry's JSON column poetry_tags
     * in the return it will load those poetry with dynamic blade page
     */
    private function getRelatedPoetry($tags = array(), $poetry_id)
    {
        $html = '';
        $poetry = Poetry::with('category', 'poet')->where('id', '!=', $poetry_id);
        foreach ($tags as $key => $value) {
            $poetry->orWhere('poetry_tags', 'like', '%' . $key . '%');
        }
        $relatedPoetry = $poetry->limit(5)->get();
        // count poetry
        $totalPoetry = count($relatedPoetry);
        // check if there is poetry available
        if ($totalPoetry > 0) {
            // loop the poetry
            foreach ($relatedPoetry as $key => $poetry) {
                $html .= view('web.poetry.related-poetry', ['total' => $totalPoetry, 'key' => $key, 'poetry' => $poetry]);
            }
        }
        return $html;
    }


    /**
     * User Comments On Poetry
     */
    private function getUserComments($poetryId, $locale, $currentUserId)
    {
        if ($currentUserId != 0) {
            $comments = UserComments::with('user')
                ->where('poetry_id', $poetryId)
                ->orderByRaw("CASE WHEN user_id = $currentUserId THEN 0 ELSE id END ASC")
                ->limit(2)
                ->get();
        } else {
            $comments = UserComments::with('user')
                ->where('poetry_id', $poetryId)
                ->orderByRaw("id ASC")
                ->limit(2)
                ->get();
        }



        // check if comments are available
        if (!is_null($comments) && count($comments) > 0) {
            $html = '';

            // send $name_sd, $name_en, $avatar, $time, $comment 

            foreach ($comments as $comnt) {
                $avatar = (file_exists($comnt->user->avatar)) ? asset($comnt->user->avatar) : $comnt->user->avatar;
                $name = ($locale == 'sd') ? $comnt->user->name_sd : $comnt->user->name;
                $time = $comnt->created_at;
                $comment = $comnt->comment;
                $editable = ($comnt->user_id == $currentUserId);
                $html .= view('web.poetry.users-comment', ['id' => $comnt->id, 'name' => $name, 'avatar' => $avatar, 'time' => $time, 'comment' => $comment, 'editable' => $editable]);
            }

            return $html;
        }

        return null;
    }


    public function loadMoreComments(Request $request)
    {

        $poetryId = $request->poetry_id;
        $lastId = $request->last_comment_id;
        $locale = app()->getLocale();

        $comments = UserComments::with('user')
            ->where('poetry_id', $poetryId)
            ->where('id', '>', $lastId) // Assuming you want to load comments after the last ID
            ->limit(1)
            ->orderBy('id', 'asc')
            ->get();

        // check if comments are available
        if ($comments->isNotEmpty()) {
            $html = '';

            // send $name_sd, $name_en, $avatar, $time, $comment 

            foreach ($comments as $comnt) {
                $avatar = (file_exists($comnt->user->avatar)) ? asset($comnt->user->avatar) : $comnt->user->avatar;
                $name = ($locale == 'sd') ? $comnt->user->name_sd : $comnt->user->name;
                $time = $comnt->created_at;
                $comment = $comnt->comment;
                $html .= view('web.poetry.users-comment', ['id' => $comnt->id, 'name' => $name, 'avatar' => $avatar, 'time' => $time, 'comment' => $comment, 'editable' => false])->render();
            }

            $notify = ['type' => 'success', 'message' => 'Data available', 'status' => 200, 'html_comments' => $html];

        } else {
            $notify = ['type' => 'success', 'message' => 'NO data available', 'status' => 403];
        }

        return response()->json($notify);
    }
}
