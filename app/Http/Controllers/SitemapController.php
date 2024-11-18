<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Couplets;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Spatie\Sitemap\SitemapIndex;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemap = SitemapIndex::create();

        $sitemap->add('sitemap/poets.xml');
        $sitemap->add('sitemap/poetry.xml');
        $sitemap->add('sitemap/couplets.xml');
        $sitemap->add('sitemap/categories.xml');
        $sitemap->add('sitemap/pages.xml');
        $sitemap->add('sitemap/tags.xml');

 
        return response($sitemap->render())->header('Content-Type', 'application/xml');
    }

    /**
     * Poets
     */
    public function poets() {
        $sitemap = SitemapIndex::create();

        $months = Poets::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
                ->distinct()
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

        foreach ($months as $month) {
            $url = route('sitemap.poets.month', ['year' => $month->year, 'month' => $month->month]);
            $sitemap->add($url);
        }

        return response($sitemap->render())->header('Content-Type', 'application/xml');
    }

    public function poetsByMonth($year, $month) {
        $sitemap = Sitemap::create();
        $poets = Poets::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();

        foreach ($poets as $poet) {
            $sitemap->add(Url::create(route('poets.slug', ['category' => null, 'name' => $poet->poet_slug]))
                ->setLastModificationDate($poet->updated_at));
        }

        return response($sitemap->render())->header('Content-Type', 'application/xml');
    }


    
    /**
     * Contents Index
     */
    public function couplets()
    {
        $indexSiteMap = SitemapIndex::create();
        
        $contents = Couplets::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total_records')
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->get()
                    ->toArray();
 
        foreach ($contents as $month) {
            if ($month['month']) {
                // Generate URLs for each month
                $year = $month['year'];
                $monthNumber = $month['month'];
                $totalRecords = $month['total_records'];

                $pages = ceil($totalRecords / 2000);

                if($pages > 1) {
                    for ($page = 1; $page <= $pages; $page++) {
                        $url = route('sitemap.couplets.month', [
                            'year' => $year,
                            'month' => $monthNumber,
                            'page' => $page,
                        ]);
                        $indexSiteMap->add($url);
                    }
                }else{
                    $url = route('sitemap.couplets.month', [
                        'year' => $year,
                        'month' => $monthNumber]);
                    $indexSiteMap->add($url);
                }
                 
            }
        }

        return response($indexSiteMap->render())->header('Content-Type', 'application/xml');
    }



    /**
     * Contents By Month
     */
    public function coupletsByMonth($year, $month)
    {
        $sitemap = Sitemap::create();
        
        $perPage = 2000;
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $contents = Couplets::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->skip($offset)
            ->take($perPage)
            ->get();

        foreach ($contents as $item) {
            $sitemap->add(
                Url::create(route('web.couplets.single', ['slug' => $item->couplet_slug]))
                    ->setLastModificationDate($item->updated_at)
            );
        }

        if ($page > 1 && $contents->isEmpty()) {
            return response()->json(['message' => 'No more contents available.'], 404);
        }

        return response($sitemap->render())->header('Content-Type', 'application/xml');
    }


    /**
     * Tags
     */
    public function tags()
    {
        $indexSiteMap = SitemapIndex::create();
        $tags = Tags::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->get();
        foreach ($tags as $month) {
            $url = route('sitemap.tags.month', ['year' => $month->year ?? '2024', 'month' => $month->month ?? '11']);
            $indexSiteMap->add($url);
        }

        return response($indexSiteMap->render())->header('Content-Type', 'application/xml');
    }

    /**
     * Tags By Month
     */
    public function tagsByMonth($year, $month)
    {
        $sitemap = Sitemap::create();
        $tags = Tags::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();

        foreach ($tags as $item) {
            $sitemap->add(Url::create(route('poetry.with-tag', ['tag' => $item->slug]))
                ->setLastModificationDate($item->updated_at));
        }

        return response($sitemap->render())->header('Content-Type', 'application/xml');
    }


    /**
     * Poetr
     */
    public function poetry() {
        $indexSiteMap = SitemapIndex::create();
        
        $contents = Poetry::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total_records')
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->get()
                    ->toArray();
 
        foreach ($contents as $month) {
            if ($month['month']) {
                // Generate URLs for each month
                $year = $month['year'];
                $monthNumber = $month['month'];
                $totalRecords = $month['total_records'];

                $pages = ceil($totalRecords / 2000);

                if($pages > 1) {
                    for ($page = 1; $page <= $pages; $page++) {
                        $url = route('sitemap.poetry.month', [
                            'year' => $year,
                            'month' => $monthNumber,
                            'page' => $page,
                        ]);
                        $indexSiteMap->add($url);
                    }
                }else{
                    $url = route('sitemap.poetry.month', [
                        'year' => $year,
                        'month' => $monthNumber]);
                    $indexSiteMap->add($url);
                }
                 
            }
        }

        return response($indexSiteMap->render())->header('Content-Type', 'application/xml');
    }

    public function poetryByMonth($year, $month) {
        $sitemap = Sitemap::create();
        
        $perPage = 2000;
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $contents = Poetry::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->skip($offset)
            ->take($perPage)
            ->get();

        foreach ($contents as $item) {
            $sitemap->add(
                Url::create(route('poetry.with-slug', ['category' => $item->category_slug, 'slug' => $item->poetry_slug]))
                    ->setLastModificationDate($item->updated_at)
            );
        }

        if ($page > 1 && $contents->isEmpty()) {
            return response()->json(['message' => 'No more poetry available.'], 404);
        }

        return response($sitemap->render())->header('Content-Type', 'application/xml');
    }

 
    /**
     * Total Categories without Index
     */
    public function categories()
    {
        $sitemap = Sitemap::create();
        $categories = Categories::get();

        foreach ($categories as $category) {
            $sitemap->add(Url::create(route('web.category', $category->id))
                ->setLastModificationDate($category->updated_at));
        }

        return response($sitemap->render())->header('Content-Type', 'application/xml');
    }

    /**
     * Website Pages 
     */
    public function pages()
    {
        $sitemap = Sitemap::create();
        $sitemap->add(Url::create(route('web.index'))->setLastModificationDate(now()));
        $sitemap->add(Url::create(route('poets.all'))->setLastModificationDate(now()));
        $sitemap->add(Url::create(route('web.couplets'))->setLastModificationDate(now()));
        $sitemap->add(Url::create(route('web.couplets.most-liked'))->setLastModificationDate(now()));
        $sitemap->add(Url::create(route('genres'))->setLastModificationDate(now()));
        $sitemap->add(Url::create(route('periods'))->setLastModificationDate(now()));
        $sitemap->add(Url::create(route('prosody'))->setLastModificationDate(now()));
        $sitemap->add(Url::create(route('web.search.index'))->setLastModificationDate(now()));
        $sitemap->add(Url::create(route('web.tags'))->setLastModificationDate(now()));
        $sitemap->add(Url::create(route('web.about'))->setLastModificationDate(now()));
         

        return response($sitemap->render())->header('Content-Type', 'application/xml');
    }
    
}
