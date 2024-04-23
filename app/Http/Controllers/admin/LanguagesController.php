<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Languages;
use Illuminate\Http\Request;

class LanguagesController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $languages = Languages::all();
        return view('admin.languages.index', compact('languages'));
    }

    public function create()
    {
        return view('admin.languages.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'lang_title' => 'required|string',
            'lang_code' => 'required|string',
            'lang_dir' => 'required|string',
            'lang_folder' => 'nullable|string',
        ]);

        Languages::create($validatedData);

        return redirect()->route('languages.index')->with('success', 'Language created successfully.');
    }

    public function show($id)
    {
        $language = Languages::find($id);
        return view('admin.languages.show', compact('language'));
    }

    public function edit($id)
    {
        $language = Languages::find($id);
        return view('admin.languages.edit', compact('language'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'lang_title' => 'required|string',
            'lang_code' => 'required|string',
            'lang_dir' => 'required|string',
            'lang_folder' => 'nullable|string',
        ]);

        $language = Languages::find($id);
        $language->update($validatedData);

        return redirect()->route('languages.index')->with('success', 'Language updated successfully.');
    }

    public function destroy($id)
    {
        $language = Languages::find($id);
        $language->delete();

        return redirect()->route('languages.index')->with('success', 'Language deleted successfully.');
    }
}
