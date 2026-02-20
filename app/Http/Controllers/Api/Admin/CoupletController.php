<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Couplets;
use Illuminate\Http\Request;

class CoupletController extends Controller
{
    public function index(Request $request)
    {
        // Fetch couplets directly, filtering for Sindhi by default as per request
        $query = Couplets::where('lang', 'sd')->with([
            'poetry' => function ($q) {
                $q->select('id', 'poetry_slug', 'category_id', 'visibility', 'is_featured', 'user_id', 'created_at');
            },
            'poetry.translations' => function ($q) {
                $q->select('id', 'poetry_id', 'lang'); // Optimizing select
            },
            'poetry.category.detail' => function ($q) {
                $q->where('lang', 'sd');
            },
            'poetry.user' => function ($q) {
                $q->select('id', 'name');
            },
            'poet_details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ]);

        if (!empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('couplet_text', 'like', "%{$search}%")
                    ->orWhereHas('poet_details', function ($fq) use ($search) {
                        $fq->where('poet_laqab', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->get('per_page', 10);
        $couplets = $query->orderBy('id', 'desc')->paginate($perPage);
        return response()->json($couplets);
    }

    public function show($id)
    {
        $couplet = Couplets::with([
            'poet_details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ])->findOrFail($id);
        return response()->json($couplet);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'couplet_text' => 'required|string',
            'couplet_slug' => 'required|unique:poetry_couplets,couplet_slug',
            'couplet_tags' => 'nullable|array',
            'lang' => 'required|string|max:10'
        ]);

        $couplet = Couplets::create([
            'poetry_id' => 0, // Independent couplet
            'poet_id' => $validated['poet_id'],
            'couplet_text' => strip_tags($validated['couplet_text'], '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
            'couplet_slug' => $validated['couplet_slug'],
            'couplet_tags' => json_encode($validated['couplet_tags'] ?? []),
            'lang' => $validated['lang'],
        ]);

        return response()->json([
            'message' => 'Couplet created successfully',
            'id' => $couplet->id
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $couplet = Couplets::findOrFail($id);

        $validated = $request->validate([
            'poet_id' => 'required|exists:poets,id',
            'couplet_text' => 'required|string',
            'couplet_slug' => 'required|unique:poetry_couplets,couplet_slug,' . $id,
            'couplet_tags' => 'nullable|array',
            'lang' => 'required|string|max:10'
        ]);

        $couplet->update([
            'poet_id' => $validated['poet_id'],
            'couplet_text' => strip_tags($validated['couplet_text'], '<p><br><b><strong><i><em><ul><ol><li><blockquote>'),
            'couplet_slug' => $validated['couplet_slug'],
            'couplet_tags' => json_encode($validated['couplet_tags'] ?? []),
            'lang' => $validated['lang'],
        ]);

        return response()->json(['message' => 'Couplet updated successfully']);
    }

    public function destroy($id)
    {
        $couplet = Couplets::findOrFail($id);
        $couplet->delete();
        return response()->json(['message' => 'Couplet moved to trash']);
    }
}
