<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:3',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $feedback = Feedback::create([
            'message' => $validated['message'],
            'rating' => $validated['rating'] ?? null,
            'user_id' => Auth::guard('sanctum')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully.',
            'data' => $feedback
        ]);
    }
}
