<?php

namespace App\Http\Controllers;

use App\Models\Lyrics;
use App\Models\Singer;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Illuminate\Http\Request;

class LyricsSpaController extends Controller
{
    public function index(Request $request, $any = null)
    {
        $path = trim($request->path(), '/');
        $segments = $path === '' ? [] : explode('/', $path);

        // Strip optional local preview prefix
        if (($segments[0] ?? '') === 'lyrics-site') {
            array_shift($segments);
        }

        $locale = 'sd';
        if (($segments[0] ?? '') === 'en' || ($segments[0] ?? '') === 'sd') {
            $locale = $segments[0];
            app()->setLocale($locale);
            array_shift($segments);
        } else {
            app()->setLocale('sd');
        }

        $isSd = $locale === 'sd';
        $title = $isSd ? 'ٻول — باک' : 'Bol — Baakh Lyrics';
        $description = $isSd
            ? 'سنڌي گيتن جا ٻول، فنڪار ۽ موسيقي — باک جو ٻول سائيٽ.'
            : 'Sindhi song lyrics, artists and music — Baakh Bol.';

        $ogImage = asset('assets/og/baakh-og-v2-1200x630.png');

        if (($segments[0] ?? '') === 'song' && isset($segments[1])) {
            $lyrics = Lyrics::with(['translations', 'singer.allDetails'])
                ->where('visibility', 1)
                ->where(function ($q) use ($segments) {
                    $q->where('lyrics_slug', $segments[1])->orWhere('id', $segments[1]);
                })
                ->first();

            if ($lyrics) {
                $sd = $lyrics->translations->firstWhere('lang', 'sd');
                $en = $lyrics->translations->firstWhere('lang', 'en');
                $songTitle = $isSd ? ($sd?->title ?: $en?->title) : ($en?->title ?: $sd?->title);
                $title = ($songTitle ?: $lyrics->lyrics_slug) . ($isSd ? ' — ٻول' : ' — Bol');
                if ($lyrics->cover_image) {
                    $ogImage = preg_match('#^https?://#i', $lyrics->cover_image)
                        ? $lyrics->cover_image
                        : asset(ltrim($lyrics->cover_image, '/'));
                }
            }
        }

        if (($segments[0] ?? '') === 'artist' && isset($segments[1])) {
            $singer = Singer::with('allDetails')
                ->where('visibility', 1)
                ->where(function ($q) use ($segments) {
                    $q->where('singer_slug', $segments[1])->orWhere('id', $segments[1]);
                })
                ->first();

            if ($singer) {
                $sd = $singer->allDetails->firstWhere('lang', 'sd');
                $en = $singer->allDetails->firstWhere('lang', 'en');
                $name = $isSd
                    ? ($sd?->singer_laqab ?: $sd?->singer_name)
                    : ($en?->singer_laqab ?: $en?->singer_name ?: $sd?->singer_laqab ?: $sd?->singer_name);
                $title = ($name ?: $singer->singer_slug) . ($isSd ? ' — فنڪار' : ' — Artist');
            }
        }

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setUrl($request->fullUrl());
        OpenGraph::addImage($ogImage);

        return view('lyrics', [
            'locale' => $locale,
            'mainSiteUrl' => rtrim((string) config('app.url'), '/'),
            'lyricsSiteUrl' => rtrim((string) config('app.lyrics_url'), '/'),
        ]);
    }
}
