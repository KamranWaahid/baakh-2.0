<?php

namespace App\Http\Controllers;

use App\Traits\BaakhLikedTrait;
use App\Traits\BaakhSeoTrait;
use Illuminate\Http\Request;

class PeriodsController extends UserController
{
    use BaakhSeoTrait, BaakhLikedTrait;

    public function index()
    {
        $title = trans('menus.period');
        $this->SEO_General($title, '');
        return view('web.periods.index');
    }

    public function poets()
    {
        return view('web.periods.poets');
    }
}
