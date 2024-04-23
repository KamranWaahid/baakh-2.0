<?php

namespace App\Http\Controllers;

use App\Models\Bundles;
use App\Models\Couplets;
use App\Models\LikeDislike;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class LikeDislikeController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
        
    }

    public function like(Request $request)
    {
        // Check if the user is logged in
        if (Auth::check()) {
            $user = Auth::user();
            $likableType = $request->likableType;
            $likableId = $request->likableId;

            // switch 
            switch ($likableType) {
                case 'Poetry':
                    $likable = Poetry::find($likableId);
                    break;
                
                case 'Bundles':
                    $likable = Bundles::find($likableId);
                    break;
                
                case 'Poets':
                    $likable = Poets::find($likableId);
                    break;
                
                case 'Couplets':
                    $likable = Couplets::find($likableId);
                    break;
                
                case 'Tags':
                    $likable = Tags::find($likableId);
                    break;
            }

            

            if (!$likable) {
                $notify = [
                    'type' => 'error',
                    'message' => $likableType.' not found'
                ];
            } else {
                // Check if the item is already liked by the user
                $existingLike = $user->likesDislikes()
                    ->where('likable_type', $likableType)
                    ->where('likable_id', $likableId)
                    ->first();

                if ($existingLike) {
                    // Remove existing likeable
                    $existingLike->delete();
                    $notify = [
                        'type' => 'success',
                        'message' => $likableType.' is Unliked Successfully'
                    ];
                } else {
                    // Like the item
                    $like = new LikeDislike(['is_like' => true, 'likable_id' => $likableId, 'likable_type'=> $likableType]);
                    $user->likesDislikes()->save($like);
                    //$likable->likesDislikes()->save($like);
                    $notify = [
                        'type' => 'success',
                        'message' => $likableType.' is Liked Successfully'
                    ];
                }
            }
        } else {
            $notify = [
                'type' => 'error',
                'message' => 'You are not authorized.. Please login'
            ];
        }

        return response()->json($notify);
    }




}
