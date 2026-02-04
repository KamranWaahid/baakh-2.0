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
}
