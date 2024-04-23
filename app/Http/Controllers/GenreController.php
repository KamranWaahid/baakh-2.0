<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\CategoryDetails;
use App\Traits\BaakhSeoTrait;
use Illuminate\Http\Request;

class GenreController extends UserController
{
    use BaakhSeoTrait;
    
    public function index()
    {
        $locale = app()->getLocale();
        $title = trans('menus.genre');
        $this->SEO_General($title, 'باک جي ھِن صفحي تي توھان سنڌي شاعريءَ جي اھم صنفن بابت معلومات حاصل ڪري سگهو ٿا ۽ گڏوگڏ انھن صنفن ۾ موجود شاعريءَ کي پڙھي سگهو ٿا.');

        $genres = Categories::with(['detail' => function ($query) use ($locale) {
            $query->where('lang', $locale);
        }])->get();
        
        return view('web.genres.index', compact('genres'));
    }

    public function show($slug)
    {
        $genre = Categories::with('detail')->where('slug', $slug)->firstOrFail();
        $info = $genre->detail;
        return view('web.genres.show', compact('info', 'genre'));
    }

    public function poetry($slug)
    {
        return view('web.genres.poetry');
    }
}
