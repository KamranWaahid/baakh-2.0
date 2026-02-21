<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ScrapeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        $scrapes = \App\Models\SindhilaScrape::orderBy('id', 'desc')->paginate($perPage);
        return response()->json($scrapes);
    }
}
