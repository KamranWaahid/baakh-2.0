<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\CategoryDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Categories::query()->with(['shortDetail']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('details', function ($q) use ($search) {
                $q->where('cat_name', 'like', "%{$search}%");
            })->orWhere('slug', 'like', "%{$search}%");
        }

        $perPage = $request->get('per_page', 10);
        $categories = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'is_featured' => 'boolean',
            'gender' => 'nullable|string',
            'content_style' => 'nullable|string',
            'details' => 'required|array',
            'details.sd' => 'required|array',
            'details.sd.cat_name' => 'required|string|max:255',
            'details.en' => 'nullable|array',
            'details.en.cat_name' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $category = Categories::create([
                'user_id' => $request->user()?->id ?? 1,
                'slug' => $validated['slug'] ?? Str::slug($validated['label']),
                'is_featured' => $validated['is_featured'] ?? false,
                'gender' => $validated['gender'],
                'content_style' => $validated['content_style'],
            ]);

            foreach ($validated['details'] as $lang => $detail) {
                if (!empty($detail['cat_name'])) {
                    CategoryDetails::create([
                        'cat_id' => $category->id,
                        'cat_name' => $detail['cat_name'],
                        'lang' => $lang,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Category created successfully',
                'data' => $category->load('details')
            ], 201);
        });
    }

    public function show($id)
    {
        $category = Categories::with('details')->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = Categories::findOrFail($id);

        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:categories,slug,' . $id,
            'is_featured' => 'boolean',
            'gender' => 'nullable|string',
            'content_style' => 'nullable|string',
            'details' => 'required|array',
            'details.sd' => 'required|array',
            'details.sd.cat_name' => 'required|string|max:255',
            'details.en' => 'nullable|array',
            'details.en.cat_name' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $category) {
            $category->update([
                'slug' => $validated['slug'],
                'is_featured' => $validated['is_featured'] ?? false,
                'gender' => $validated['gender'],
                'content_style' => $validated['content_style'],
            ]);

            foreach ($validated['details'] as $lang => $detail) {
                if (!empty($detail['cat_name'])) {
                    CategoryDetails::updateOrCreate(
                        ['cat_id' => $category->id, 'lang' => $lang],
                        ['cat_name' => $detail['cat_name']]
                    );
                }
            }

            return response()->json([
                'message' => 'Category updated successfully',
                'data' => $category->load('details')
            ]);
        });
    }

    public function destroy($id)
    {
        $category = Categories::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
