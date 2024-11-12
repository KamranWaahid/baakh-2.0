<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doodle;
use App\Models\Poets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DoodlesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doodles = Doodle::orderBy('id', 'desc')->get();
        return view('admin.doodles.index', compact('doodles'));
    }

    /**
     * Trashed Doodles
     */
    public function trashed()
    {
        $doodles = Doodle::orderBy('deleted_at', 'desc')->onlyTrashed()->get();
        return view('admin.doodles.trashed', compact('doodles')); 
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $poets = Cache::rememberForever('admin_poets_ids', function () {
            return Poets::select('id')->get();
        });
        return view('admin.doodles.create', compact('poets'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpg,webp,svg,png',
            'link_url' => 'nullable|url',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'poet_id' => 'nullable|integer',
        ]);

        $data = $request->only([
            'title', 'image', 'link_url', 'link_title', 'start_date', 'end_date'
        ]);

        if($request->filled('poet_id')) {
            $data['reference_type'] = Poets::class;
            $data['reference_id'] = $request->poet_id;
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName(); // Generate a unique name for the image
            $imagePath = 'assets/images/sliders/' . $imageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $image->move(public_path('assets/images/sliders'), $imageName);
    
            $data['image'] = $imagePath; // Store the image path in the $sliderData array
        }

        Doodle::create($data);
        return redirect()->route('admin.doodles.index')->with('success', 'New doodle has been added');
    }

    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $doodle = Doodle::findOrFail($id);
        $poets = Cache::rememberForever('admin_poets_ids', function () {
            return Poets::select('id')->get();
        });
        return view('admin.doodles.edit', compact('doodle', 'poets'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $doodle = Doodle::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpg,webp,svg',
            'link_url' => 'nullable|url',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'poet_id' => 'nullable|integer',
        ]);

        $data = $request->only([
            'title', 'image', 'link_url', 'link_title', 'start_date', 'end_date'
        ]);

        $oldFile = $doodle->image;

        if($request->filled('poet_id')) {
            $data['reference_type'] = Poets::class;
            $data['reference_id'] = $request->poet_id;
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = 'assets/images/sliders/' . $imageName;
    
            $image->move(public_path('assets/images/sliders'), $imageName);
    
            $data['image'] = $imagePath; 
            if(file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        $doodle->update($data);
        return redirect()->route('admin.doodles.index')->with('success', 'Doodle updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doodle $doodle)
    {
        $doodle->delete();

        $message = [
            'message' => 'Doodle is moved to trash',
            'type' => 'success',
            'icon' => ''
        ];
        return response()->json($message);
    }

    /**
     * Permanently Delete
     */
    public function hardDelete($id){
        $doodle = Doodle::withTrashed()->findOrFail($id);
            
        $doodle->forceDelete();

        if(file_exists($doodle->image)){
            unlink($doodle->image);
        }

        $message = [
            'message' => 'Doodle permanently deleted',
            'type' => 'success',
            'icon' => ''
        ];

        return response()->json($message);
    }

    /**
     * restore doodle
     */
    public function restore($id)
    {
        $slider = Doodle::withTrashed()->findOrFail($id);
        $slider->restore();

        // array for message
        $message = [
            'message' => 'Doodle restored successfully',
            'type' => 'success',
            'icon' => ''
        ];

        return response()->json($message);
    }
}
