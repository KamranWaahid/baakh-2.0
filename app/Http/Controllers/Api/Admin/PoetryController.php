<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poetry;
use Illuminate\Http\Request;

class PoetryController extends Controller
{
    public function index(Request $request)
    {
        $query = Poetry::with([
            'info' => function ($q) {
                $q->where('lang', 'sd');
            },
            'poet_details' => function ($q) {
                $q->where('lang', 'sd');
            },
            'category.detail' => function ($q) {
                $q->where('lang', 'sd');
            },
            'user' => function ($q) {
                $q->select('id', 'name');
            }
        ]);

        if ($request->has('type') && $request->type === 'couplet') {
            $query->with(['couplets' => function ($q) {
                $q->orderBy('id', 'asc');
            }]);
            // Filter where category_id is NULL for independent couplets
            // OR where it has a category but we want to show it as a couplet? 
            // The user said "if couplet has category it show linked, otherwise indepented".
            // So couplets CAN have categories. 
            // BUT couplets created via the new form won't have categories.
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('info', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })->orWhereHas('poet_details', function ($q) use ($search) {
                $q->where('poet_laqab', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        $poetry = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($poetry);
    }

    public function show($id)
    {
        $poetry = Poetry::with(['translations', 'couplets', 'category', 'poet'])->findOrFail($id);
        return response()->json($poetry);
    }

    public function destroy($id)
    {
        $poetry = Poetry::findOrFail($id);
        $poetry->delete();
        return response()->json(['message' => 'Poetry moved to trash']);
    }

    public function toggleVisibility($id)
    {
        $poetry = Poetry::findOrFail($id);
        $poetry->visibility = $poetry->visibility == 1 ? 0 : 1;
        $poetry->save();

        return response()->json([
            'message' => 'Visibility updated',
            'visibility' => $poetry->visibility
        ]);
    }

    public function toggleFeatured($id)
    {
        $poetry = Poetry::findOrFail($id);
        $poetry->is_featured = $poetry->is_featured == 1 ? 0 : 1;
        $poetry->save();

        return response()->json([
            'message' => 'Featured status updated',
            'is_featured' => $poetry->is_featured
        ]);
    }
    public function create()
    {
        $poets = \App\Models\Poets::with(['details' => function($q) {
            $q->where('lang', 'sd');
        }])->select('id', 'poet_slug')->get()->map(function($poet) {
            return [
                'id' => $poet->id,
                'name' => $poet->details->first()?->poet_laqab ?? $poet->poet_slug
            ];
        });

        $categories = \App\Models\Categories::with(['detail' => function($q) {
            $q->where('lang', 'sd');
        }])->select('id', 'slug')->get()->map(function($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->detail?->cat_name ?? $cat->slug
            ];
        });

        $tags = \App\Models\Tags::where('lang', 'sd')->select('id', 'tag', 'slug')->get();

        return response()->json([
            'poets' => $poets,
            'categories' => $categories,
            'tags' => $tags,
            'content_styles' => ['justified', 'center', 'start', 'end']
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'poet_id' => 'required|exists:poets,id',
            'category_id' => 'nullable|exists:categories,id',
            'poetry_slug' => 'required|unique:poetry_main,poetry_slug',
            'poetry_title' => 'required|string|max:255',
            'content_style' => 'required|string',
            'visibility' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'couplets' => 'required|array|min:1',
            'couplets.*.couplet_text' => 'required|string',
            'poetry_tags' => 'nullable|array',
            'poetry_info' => 'nullable|string',
            'source' => 'nullable|string'
        ]);

        \DB::beginTransaction();
        try {
            $poetry = Poetry::create([
                'poet_id' => $validated['poet_id'],
                'category_id' => $validated['category_id'],
                'user_id' => \Auth::id(),
                'poetry_slug' => $validated['poetry_slug'],
                'poetry_tags' => json_encode($validated['poetry_tags'] ?? []),
                'visibility' => $validated['visibility'],
                'is_featured' => $validated['is_featured'],
                'content_style' => $validated['content_style'],
            ]);

            $poetry->translations()->create([
                'title' => $validated['poetry_title'],
                'info' => $validated['poetry_info'] ?? null,
                'source' => $validated['source'] ?? null,
                'lang' => 'sd', // Default lang for creation
            ]);

            foreach ($validated['couplets'] as $index => $couplet) {
                $poetry->couplets()->create([
                    'couplet_text' => $couplet['couplet_text'],
                    'poet_id' => $validated['poet_id'],
                    'couplet_slug' => $validated['poetry_slug'] . '-' . ($index + 1)
                ]);
            }

            \DB::commit();
            return response()->json(['message' => 'Poetry created successfully', 'id' => $poetry->id], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to create poetry: ' . $e->getMessage()], 500);
        }
    }
}
