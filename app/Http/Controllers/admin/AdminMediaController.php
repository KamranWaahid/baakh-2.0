<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Languages;
use App\Models\Media;
use App\Models\Poetry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMediaController extends Controller
{
    public function index($id)
    {
        $poetry = Poetry::findOrFail($id);
        $languages = Languages::all();
        $db_media = Media::where('poetry_id', $poetry->id)->get();

        $media = $db_media->unique('media_url')->values()->all();
        

        return view('admin.media.create', compact('poetry', 'media', 'languages'));
    }

    public function edit($id)
    {
        
    }

    public function store(Request $request)
    {
        $request->validate([
            'poetry_id' => 'required',
            'media_type' => 'required'
        ]);

        if ($request->hasFile('audio')) {
            $audio = $request->file('audio');
            $uniqueid = uniqid();
            $extension = $request->file('audio')->getClientOriginalExtension();
            $audioName = Carbon::now()->format('Ymd').'_'.$uniqueid.'.'.$extension;
            $mediaUrl = 'assets/poetry/media/' . $audioName; // Specify the storage path
    
            // Move the uploaded audio file to the storage path
            $audio->move(public_path('assets/poetry/media/'), $audioName);

        }else{
            $mediaUrl = $request->media_url;
        }

        $total = count($request->input('lang'));
        for ($i=0; $i < $total; $i++) { 
            $data = [
                'media_type' => $request->media_type,
                'media_url' => $mediaUrl,
                'poetry_id' => $request->poetry_id,
                'media_title' => $request->input('media_title')[$i],
                'lang' => $request->input('lang')[$i],
                'is_visible' => 1
            ];
            Media::create($data);
        }

        return to_route('admin.media.create', $request->poetry_id);
    }

    public function destroy($id)
    {
        // Use "find" to retrieve a record by ID
        $mediaToDelete = Media::find($id);
    
        if (!$mediaToDelete) {
            $message = [
                'message' => 'Media not found',
                'type' => 'error',
            ];
            return response()->json($message);
        }
    
        // Get all records with the same media_url
        $media = Media::where('media_url', $mediaToDelete->media_url)->get();
    
        // Loop through and delete each record
        foreach ($media as $mediaItem) {
            // Check and unlink the associated file if it exists
            if (file_exists($mediaItem->media_url)) {
                unlink($mediaItem->media_url);
            }
    
            $mediaItem->forceDelete();
        }
    
        $message = [
            'message' => 'Media deleted',
            'type' => 'success',
        ];
    
        return response()->json($message);
    }
    
}
