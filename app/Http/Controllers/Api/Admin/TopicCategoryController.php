<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TopicCategory;
use Illuminate\Http\Request;

class TopicCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(TopicCategory::orderBy('name')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $validated['slug'] = \Str::slug($request->name);

        $topicCategory = TopicCategory::create($validated);

        return response()->json($topicCategory, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TopicCategory $topicCategory)
    {
        return response()->json($topicCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $topicCategory = TopicCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $validated['slug'] = \Str::slug($request->name);

        $topicCategory->update($validated);

        return response()->json($topicCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $topicCategory = TopicCategory::findOrFail($id);
        $topicCategory->delete();

        return response()->json(['message' => 'Topic category deleted successfully']);
    }
}
