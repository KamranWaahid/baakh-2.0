<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Poets;
use Illuminate\Http\Request;

class BaakhSearchController extends Controller
{
    public function index()
    {
        dd(request()->query());
    }

    /**
     * Generate JSON files
     */
    public function generateJson()
    {
        $poets = Poets::select([
            'id', 'poet_slug', 'poet_pic'
        ])->with('shortDetail:id,poet_id,poet_laqab')->get();

        $categories = Categories::select([
            'id', 'slug'
        ])->with('shortDetail:id,cat_id,cat_name,cat_name_plural')->get();

        $content = [];

        foreach ($categories as $cat) {
                $content[] = [
                    'route' => $cat->slug,
                    'keyword' => ' جا ' . $cat->shortDetail->cat_name_plural
                ];
                // $content[] = ['slug' => $cat->slug, 'name' => $cat->shortDetail->cat_name, 'name_plural' => $cat->shortDetail->cat_name_plural];
            }

        // foreach ($poets as $poet) {
        //     $content[] = [
        //         'route' => route('poets.slug', ['category' => null, 'name' => $poet->poet_slug]),
        //         'keyword' => $poet->shortDetail->poet_laqab
        //     ];
        //     // foreach ($categories as $cat) {
        //     //     $content[] = [
        //     //         'route' => route('poets.slug', ['category' => $cat->slug, 'name' => $poet->poet_slug]),
        //     //         'keyword' => $poet->shortDetail->poet_laqab . ' جا ' . $cat->shortDetail->cat_name_plural
        //     //     ];
        //     //     // $content[] = ['slug' => $cat->slug, 'name' => $cat->shortDetail->cat_name, 'name_plural' => $cat->shortDetail->cat_name_plural];
        //     // }
        // }

        

        return response()->json($content, 200, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
