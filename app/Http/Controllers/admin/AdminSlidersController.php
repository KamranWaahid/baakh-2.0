<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Languages;
use App\Models\Sliders;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AdminSlidersController extends Controller
{
    public function index()
    {
        $sliders = Sliders::with('language')->get();
        
        return view('admin.sliders.index', compact('sliders'));
    }

    public function trashed()
    {
        $sliders = Sliders::with('language')->onlyTrashed()->get();
        
        return view('admin.sliders.trashed', compact('sliders'));
    }

    public function create()
    {
        $languages = Languages::all();
        return view('admin.sliders.create', compact('languages'));
    }

    public function store(Request $request)
    {
        $sliderData = $request->validate([
            'title' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp',
            'link_url' => 'nullable',
            'category' => 'nullable',
            'lang' => 'required',
            'visibility' => 'nullable',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName(); // Generate a unique name for the image
            $imagePath = 'assets/images/sliders/' . $imageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $image->move(public_path('assets/images/sliders'), $imageName);
    
            $sliderData['image'] = $imagePath; // Store the image path in the $sliderData array
        }

        Sliders::create([
            'title' => $request->title,
            'image' => $imagePath ?? null, // Use $imagePath if it's set, otherwise null
            'link_url' => $request->link_url ?? null,
            'category' => $request->category ?? null,
            'lang' => $request->lang,
            'visibility' => ($request->visibility) ? 1 : 0,
        ]);

        return redirect()->route('admin.sliders.index')
            ->with('success', 'Slider created successfully.');
    }

    public function edit($id)
    {
        $slider = Sliders::findOrFail($id);
        $languages = Languages::all();
        return view('admin.sliders.edit', compact('slider', 'languages'));
    }

    public function update(Request $request, $id)
    {
        $slider = Sliders::findOrFail($id);

        $request->validate([
            'title' => 'required',
            'image' => 'required',
            'link_url' => 'nullable',
            'category' => 'nullable',
            'lang' => 'required'
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName(); // Generate a unique name for the image
            $imagePath = 'assets/images/sliders/' . $imageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $image->move(public_path('assets/images/sliders'), $imageName);
    
            // delete old image
            if(file_exists($slider->image)){
                unlink($slider->image);
            }
        }

        $slider->update([
            'title' => $request->title,
            'image' => $imagePath ?? null, // Use $imagePath if it's set, otherwise null
            'link_url' => $request->link_url ?? null,
            'category' => $request->category ?? null,
            'lang' => $request->lang,
            'visibility' => ($request->visibility) ? 1 : 0,
        ]);

        return redirect()->route('admin.sliders.index')
            ->with('success', 'Slider updated successfully.');
    }

    public function destroy($id)
    {
        $slider = Sliders::findOrFail($id);
        $slider->delete();
        $message = [
            'message' => 'Slider\'s information moved to trash',
            'type' => 'success',
            'icon' => ''
        ];
        return response()->json($message);
    }


    // restore
    public function restore($id){
        try {
            $slider = Sliders::withTrashed()->findOrFail($id);
            $slider->restore();

            // array for message
            $message = [
                'message' => 'Slider restored successfully',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Slider not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

    // permanent delete
    public function hardDelete($id){
        try {
            $slider = Sliders::withTrashed()->findOrFail($id);
            
            $slider->forceDelete();

            if(file_exists($slider->image)){
                unlink($slider->image);
            }

            // array for message
            $message = [
                'message' => 'Slider\'s information permanently deleted',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Slider not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

     /*
    * Toggle Visiblity
    */
    public function toggleVisibility($id)
    {
        try {
            $slider = Sliders::findOrFail($id);
            
            $newVisibility = $slider->visibility === 1 ? 0 : 1;

            $slider->update([
                'visibility' => $newVisibility
            ]);

            $message = [
                'message' => 'Slider\'s visibility toggled successfully',
                'type' => 'success',
                'visibility' => $newVisibility
            ];

            return response()->json($message);
        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Bundle not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }
}
