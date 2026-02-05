<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Languages;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $languages = Languages::latest()->get();
        return response()->json($languages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'lang_title' => 'required|string',
            'lang_code' => 'required|string|unique:languages,lang_code',
            'lang_dir' => 'required|string|in:ltr,rtl',
            'lang_folder' => 'nullable|string',
            'is_default' => 'boolean'
        ]);

        if (isset($validatedData['is_default']) && $validatedData['is_default']) {
            Languages::where('is_default', 1)->update(['is_default' => 0]);
        }

        $language = Languages::create($validatedData);

        ActivityLog::log('created_language', $request->user(), null, "Created language: {$language->lang_title}");

        return response()->json([
            'message' => 'Language created successfully',
            'language' => $language
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $language = Languages::findOrFail($id);
        return response()->json($language);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $language = Languages::findOrFail($id);

        $validatedData = $request->validate([
            'lang_title' => 'required|string',
            'lang_code' => 'required|string|unique:languages,lang_code,' . $id,
            'lang_dir' => 'required|string|in:ltr,rtl',
            'lang_folder' => 'nullable|string',
            'is_default' => 'boolean'
        ]);

        if (isset($validatedData['is_default']) && $validatedData['is_default']) {
            Languages::where('is_default', 1)->where('id', '!=', $id)->update(['is_default' => 0]);
        }

        $language->update($validatedData);

        ActivityLog::log('updated_language', $request->user(), null, "Updated language: {$language->lang_title}");

        return response()->json([
            'message' => 'Language updated successfully',
            'language' => $language
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $language = Languages::findOrFail($id);

        if ($language->is_default) {
            return response()->json(['message' => 'Cannot delete the default language'], 400);
        }

        $language->delete();

        ActivityLog::log('deleted_language', request()->user(), null, "Deleted language: {$language->lang_title}");

        return response()->json(['message' => 'Language deleted successfully']);
    }
}
