<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserComments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserCommentsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function poetryComments(Request $request)
    {
        $poetryId = $request->poetry_id;
        // todo : fetch all comments of poetry including User's comment
    }

    public function postComment(Request $request)
    {
        // todo : User can add comment on poetry
        $poetryId = $request->poetry_id;
        $userId = Auth::user()->id;
        $userComment = $request->comment;
        $avtar = $request->avatar;
        $name = $request->user_name;

        $data = [
            'poetry_id' => $poetryId,
            'user_id' => $userId,
            'comment' => $userComment
        ];
       
        $send = UserComments::create($data);

        if($send)
        {
            $htmlComment = view('web.poetry.users-comment', ['id'=> $send->id,'avatar' => $avtar, 'comment' => $userComment, 'name' => $name, 'time' => 'ڪُجهہ سيڪنڊ اڳ', 'editable' => true])->render();

            $notify = [
                'type' => 'success',
                'message' => 'Your comment is submitted.',
                'comment' => $htmlComment
            ];
        }
        else {
            $notify = [
                'type' => 'error',
                'message' => 'Error while submitting your comment'
            ];
        }
        return response()->json($notify);
    }

    public function updateComment(Request $request)
    {
        $commentId = $request->comment_id;
        $userId = Auth::user()->id;
        $poetryId = $request->poetry_id;

        $find = UserComments::where(['id'=>$commentId, 'user_id'=>$userId, 'poetry_id'=>$poetryId])->first();
        if(!is_null($find))
        {
            // update comment
            $find->comment = $request->comment;
            if($find->save())
            {
                $avtar = $request->avatar;
                $name = $request->user_name; 
                $htmlComment = view('web.poetry.users-comment', ['id'=> $commentId,'avatar' => $avtar, 'comment' => $request->comment, 'name' => $name, 'time' => 'ڪُجهہ سيڪنڊ اڳ', 'editable' => true])->render();

                $notify = [
                    'type' => 'success',
                    'message' => 'Your comment is submitted.',
                    'comment' => $htmlComment
                ];
            }
            else {
                $notify = [
                    'type' => 'error',
                    'message' => 'Error while submitting your comment'
                ];
            }
        }else {
            $notify = [
                'type' => 'error',
                'message' => 'You are not authorized to update this comment..'
            ];
        }

        
        return response()->json($notify);
    }

     
}
