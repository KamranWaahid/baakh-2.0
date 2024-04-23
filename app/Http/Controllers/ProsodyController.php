<?php

namespace App\Http\Controllers;

use App\Traits\BaakhLikedTrait;
use App\Traits\BaakhSeoTrait;
use Illuminate\Http\Request;

class ProsodyController extends UserController
{
    use BaakhSeoTrait, BaakhLikedTrait;

    public function index()
    {
        $title = trans('menus.prosody');
        $this->SEO_General($title, '');
        return view('web.prosody.index');
    }

    public function result()
    {
        return view('web.prosody.result');
    }
}
