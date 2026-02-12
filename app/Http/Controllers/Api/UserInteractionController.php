<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserLikes;
use App\Models\UserBookmark;
use App\Models\Poetry;
use App\Models\Couplets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserInteractionController extends Controller
{
    public function toggleLike(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'type' => 'required|string|in:poem,couplet'
        ]);

        $userId = Auth::id();
        $likeableId = $request->id;
        $likeableType = $request->type === 'poem' ? Poetry::class : Couplets::class;

        $like = UserLikes::where([
            'user_id' => $userId,
            'likeable_id' => $likeableId,
            'likeable_type' => $likeableType
        ])->first();

        if ($like) {
            $like->delete();
            $status = 'unliked';
        } else {
            UserLikes::create([
                'user_id' => $userId,
                'likeable_id' => $likeableId,
                'likeable_type' => $likeableType
            ]);
            $status = 'liked';
        }

        // Get updated count
        $count = UserLikes::where([
            'likeable_id' => $likeableId,
            'likeable_type' => $likeableType
        ])->count();

        return response()->json([
            'status' => 'success',
            'interaction' => $status,
            'count' => $count
        ]);
    }

    public function toggleBookmark(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'type' => 'required|string|in:poem,couplet'
        ]);

        $userId = Auth::id();
        $bookmarkableId = $request->id;
        $bookmarkableType = $request->type === 'poem' ? Poetry::class : Couplets::class;

        $bookmark = UserBookmark::where([
            'user_id' => $userId,
            'bookmarkable_id' => $bookmarkableId,
            'bookmarkable_type' => $bookmarkableType
        ])->first();

        if ($bookmark) {
            $bookmark->delete();
            $status = 'unbookmarked';
        } else {
            UserBookmark::create([
                'user_id' => $userId,
                'bookmarkable_id' => $bookmarkableId,
                'bookmarkable_type' => $bookmarkableType
            ]);
            $status = 'bookmarked';
        }

        return response()->json([
            'status' => 'success',
            'interaction' => $status
        ]);
    }
}
