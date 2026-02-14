<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the feedback.
     */
    public function index(Request $request)
    {
        $query = Feedback::with('user');

        if ($request->rating) {
            $query->where('rating', $request->rating);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Display the specified feedback.
     */
    public function show(Feedback $feedback)
    {
        return $feedback->load('user');
    }

    /**
     * Remove the specified feedback.
     */
    public function destroy(Feedback $feedback)
    {
        $feedback->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feedback deleted successfully.'
        ]);
    }
}
