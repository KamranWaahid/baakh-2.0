<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestBundleValidation;
use App\Models\BundleItems;
use App\Models\Bundles;
use App\Models\BundleTranslations;
use App\Models\Couplets;
use App\Models\Languages;
use App\Models\Poetry;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AdminBundlesController extends Controller
{
    // Bundles
    

    public function index()
    {
        $bundles = Bundles::all();
        $languages = Languages::all();
        //$bundles = $this->bundleContent();
        //dd($bundles);
        return view('admin.bundles.index', compact('bundles', 'languages'));
    }

    // Trashed

    public function trashed(){
        $bundles = Bundles::onlyTrashed()->get();
        return view('admin.bundles.trashed', compact('bundles'));
    }

    public function create()
    {
        return view('admin.bundles.create');
    }

    // add data
    public function store(RequestBundleValidation $request)
    {
        //dd($request->all());
          
        // if has thumbnail
        if ($request->hasFile('bundle_thumbnail')) {
            $image = $request->file('bundle_thumbnail');
            $imageName = time() . '_thumb_' . $image->getClientOriginalName(); // Generate a unique name for the image
            $imagePath = 'assets/images/bundles/' . $imageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $image->move(public_path('assets/images/bundles'), $imageName);
        }

        // if has cover
        if ($request->hasFile('bundle_cover')) {
            $coverImage = $request->file('bundle_cover');
            $coverImageName = time() . '_cover_' . $coverImage->getClientOriginalName(); // Generate a unique name for the image
            $coverImagePath = 'assets/images/bundles/' . $coverImageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $coverImage->move(public_path('assets/images/bundles'), $coverImageName);
        }

        $bundle = Bundles::create([
            'title' => $request->title,
            'slug' => $request->slug,
            'bundle_thumbnail' => $imagePath ?? null, // Use $imagePath if it's set, otherwise null
            'bundle_cover' => $coverImagePath ?? null,
            'bundle_layout' => $request->bundle_type ?? null,
            'description' => $request->description ?? null,
            'is_featured' => $request->is_featured ?? 0,
            'is_visible' => $request->is_visible ?? 0
        ]);

      
        // count bundle items
        $totalItems = count($request->input('reference_id'));
        for ($i=0; $i < $totalItems; $i++) { 
          // Create a new instance of BundleItems and set the reference_id and reference_type
            $bundleItem = new BundleItems([
                'reference_id' => $request->input('reference_id')[$i],
                'reference_type' => $request->input('reference_type')[$i],
                
            ]);
            // Save the bundle item to associate it with the bundle
            $bundle->items()->save($bundleItem);
        }

        return redirect()->route('admin.bundle.index')
            ->with('success', 'Bundle created successfully.');

    }

    public function edit($id)
    {
        if(request()->input('lang') && request()->input('lang') !='sd')
        {
            $lang = request()->input('lang');
            $file_path = 'admin.bundles.translation';
        }else{
            $lang = null;
            $file_path = 'admin.bundles.edit';
        }
        $bundle = Bundles::findOrFail($id);
        $items = $bundle->items()->with('reference')->get();
        $translations = $lang ? $bundle->translations()->where('lang_code', $lang)->first() : null;

        $languages = Languages::where('lang_code', $lang)->first();
        
        return view($file_path, compact('bundle', 'items', 'translations', 'languages'));
    }

    /* public function edit($id)
    {
        
        $lang = request()->input('lang');
        //dd("Accessing edit with ID: {$lang}");
        $bundleQuery = Bundles::with(['items.reference']);
    
        if ($lang) {
            $bundleQuery = $bundleQuery->whereHas('translations', function ($query) use ($lang) {
                $query->where('lang_code', $lang);
            });
        }
    
        $bundle = $bundleQuery->findOrFail($id);
    
        // Assuming you need translations separately or for display adjustments.
        // You might need to pass translations separately to your view if you need to display or edit them specifically.
        $translations = $lang ? $bundle->translations()->where('lang_code', $lang)->first() : null;
    
        return view('admin.bundles.edit', compact('bundle', 'translations'));
    } */

    public function update(RequestBundleValidation $request, $id)
    {
        $bundle = Bundles::findOrFail($id);
        
        // Update images if they are provided
        if ($request->hasFile('bundle_thumbnail')) {
            $image = $request->file('bundle_thumbnail');
            $imageName = time() . '_thumb_' . $image->getClientOriginalName(); // Generate a unique name for the image
            $imagePath = 'assets/images/bundles/' . $imageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $image->move(public_path('assets/images/bundles'), $imageName);
            
            $oldThumb = $bundle->bundle_thumbnail;

            // unlink old image
            if($oldThumb && file_exists($oldThumb)){
                unlink($oldThumb);
            }
    
            $bundle->bundle_thumbnail = $imagePath; // Store the image path in the $sliderData array
            
        }

        if ($request->hasFile('bundle_cover')) {
            $coverImage = $request->file('bundle_cover');
            $coverImageName = time() . '_cover_' . $coverImage->getClientOriginalName(); // Generate a unique name for the image
            $coverImagePath = 'assets/images/bundles/' . $coverImageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $coverImage->move(public_path('assets/images/bundles'), $coverImageName);

            // unlink old image
            $oldCover = $bundle->bundle_cover;
            if($oldCover && file_exists($oldCover)){
                unlink($oldCover);
            }
    
            $bundle->bundle_cover = $coverImagePath; // Store the image path in the $sliderData array
        }

        
        $bundle->title = $request->title;
        $bundle->slug = $request->slug;
        $bundle->bundle_layout = $request->bundle_type ?? null;
        $bundle->description = $request->description ?? null;
        $bundle->is_featured = $request->is_featured ?? 0;
        $bundle->is_visible = $request->is_visible ?? 0;

 
        // count bundle items
        $totalItems = count($request->input('reference_id'));
        $bundle->items()->forceDelete();

        for ($i=0; $i < $totalItems; $i++) { 
            $bundleItem = new BundleItems([
                'reference_id' => $request->input('reference_id')[$i],
                'reference_type' => $request->input('reference_type')[$i],
            ]);
            // Save the bundle item to associate it with the bundle
            $bundle->items()->save($bundleItem);
        }
        
        // Save the updated bundle
        $bundle->save();

        return redirect()->route('admin.bundle.index')
            ->with('success', 'Bundle updated successfully.');
    }

    /**
     * Update Translation of Bundle
     * route = admin.bundle.edit-translation
     */
    public function update_translation(Request $request, $id)
    {
        $request->validate([
           'lang' => 'required|min:2',
           'title' => 'required',
           'description' => 'required',
        ]);

        $bundle = Bundles::findOrFail($id);

        // check if exists translation
        $exists = BundleTranslations::where('bundle_id', $id)->first();
        if($exists)
        {
            $updated = $bundle->translations()->update([
                'lang_code' => $request->lang,
                'title' => $request->title,
                'description' => $request->description,
            ]);
            
            // save translation and show message
            if($updated)
            {
                $message = ['success', 'Bundle translation updated successfully'];
            }else{
                $message = ['error', 'Error while updating Bundle translation'];
            }
        }else{
            $created = BundleTranslations::create([
                'bundle_id' => $id,
                'lang_code' => $request->lang,
                'title' => $request->title,
                'description' => $request->description,
            ]);
            if($created)
            {
                $message = ['success', 'Successfully added new translation to Bundle'];
            }else{
                $message = ['error', 'Error while adding Bundle translation'];
            }
        }

        return redirect()->route('admin.bundle.index')
            ->with($message);
    }


    /**
     * Move to trash function
     */
    public function destroy($id)
    {
        $bundle = Bundles::findOrFail($id);
        $bundle->delete();
        $message = [
            'message' => 'Bundle moved to trash',
            'type' => 'success',
            'icon' => ''
        ];
        return response()->json($message);
    }


    /**
     * Restore from Trash function
     */
    public function restore($id){
        try {
            $bundle = Bundles::withTrashed()->findOrFail($id);
            $bundle->restore();

            // array for message
            $message = [
                'message' => 'Bundle restored successfully',
                'type' => 'success',
                'icon' => ''
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

    /**
     * Permanently Delete Bundle with its Items, translaitons
     */
    public function hardDelete($id){
        try {
            $bundle = Bundles::withTrashed()->findOrFail($id);
            $oldThumb =$bundle->bundle_thumbnail;
            $oldCover = $bundle->bundle_cover;
            
            $bundle->forceDelete();

            if($oldCover){
                if(file_exists($oldCover)){
                    unlink($oldCover);
                }
            }
            if($oldThumb){
                if(file_exists($oldThumb)){
                    unlink($oldThumb);
                }
            }
            
            // array for message
            $message = [
                'message' => 'Bundle\'s information permanently deleted',
                'type' => 'success',
                'icon' => ''
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

    
    /*
    * Toggle Visiblity
    * to show and hide bundle with visibilty
    */
    public function toggleVisibility($id)
    {
        try {
            $bundle = Bundles::findOrFail($id);
            
            $newVisibility = $bundle->is_visible === 1 ? 0 : 1;

            $bundle->update([
                'is_visible' => $newVisibility
            ]);

            $message = [
                'message' => 'Bundle\'s visibility toggled successfully',
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

    /**
     * Toggle Featured
     * Set and unset Featured Bundle
     */
    public function toggleFeatured($id){
        try {
            $bundle = Bundles::findOrFail($id);
            
            $newVisibility = $bundle->is_featured === 1 ? 0 : 1;

            $bundle->update([
                'is_featured' => $newVisibility
            ]);

            $message = [
                'message' => 'Bundle\'s featured toggled successfully',
                'type' => 'success',
                'featured' => $newVisibility
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


    /**
     * Search Couplets function
     * used in ajax request
     */
    public function searchCouplets(Request $request){
        // Get the type of poetry
        $type = $request->input('type');
        $poet_id = $request->input('poet');
        // Get the search term from the Ajax request
        $searchTerm = urldecode($request->input('term'));
        // Get the requested page number from the Ajax request
        $page = $request->input('page');

        // Set the number of results per page
        $perPage = 4; // You can adjust this value as per your requirement
        
        // Calculate the offset based on the requested page number
        $offset = ($page - 1) * $perPage;
        $data = [];
        if($type == 'couplets'){
            // Use Eloquent to retrieve paginated results
            $couplets = Couplets::where('couplet_text', 'LIKE', '%'.$searchTerm.'%')
                                ->where('lang', 'sd')
                                ->where('poet_id', $poet_id)
                                ->skip($offset)
                                ->take($perPage)
                                ->where('deleted_at', null)
                                ->get();
                                
            foreach ($couplets as $couplet) {
                $data[] = [
                    'id' => $couplet->id,
                    'text' => $couplet->couplet_text,
                    'model' => Couplets::class,
                    'poet_id' => $poet_id
                ];
            }
        }else{
            // Use Eloquent to retrieve paginated results
            $poetry = Poetry::where('poetry_title', 'LIKE', '%'.$searchTerm.'%')
                                ->where('lang', 'sd')
                                ->where('poet_id', $poet_id)
                                ->skip($offset)
                                ->take($perPage)
                                ->where('deleted_at', null)
                                ->get();
                                
            foreach ($poetry as $p) {
                $data[] = [
                    'id' => $p->id,
                    'text' => $p->poetry_title,
                    'model' => Poetry::class,
                    'poet_id' => $poet_id
                ];
            }
        }

        if (empty($data)) {
            return response()->json(['message' => 'Data not available'], 404);
        } else {
            return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * Bundle Data with Translations
     * This will be used in Index and other parts
     */
    public function bundleContent()
    {
        
        $bundles = Bundles::with('translations')->get();

        $results = [];
        foreach ($bundles as $bundle) {
            // Get the primary translation directly from the bundle
            $primaryTranslation = [
                'lang_title' => $bundle->title, // Assuming title is the primary content
                'edit_route' => route('admin.bundle.edit', ['id' => $bundle->id, 'lang_code' => 'sd']),
            ];

            $availableLanguages = [];
            foreach ($bundle->translations as $translation) {
                if ($translation->lang_code === 'sd') {
                    continue; // Skip primary language, as it's already included
                }

                $langTitle = $translation->title;
                $langCode = $translation->lang_code;
                $editRoute = route('admin.bundle.edit', ['id' => $bundle->id, 'lang_code' => $langCode]);
                $availableLanguages[] = [
                    'lang_title' => $langTitle,
                    'edit_route' => $editRoute,
                ];
            }

            // Add primary translation to available languages
            $availableLanguages[] = $primaryTranslation;

            $result = [
                'id' => $bundle->id,
                'title' => $bundle->title,
                'slug' => $bundle->slug,
                'bundle_thumbnail' => $bundle->bundle_thumbnail,
                'user_name' => $bundle->user->name, // Assuming user relationship is set up
                'available_languages' => $availableLanguages,
            ];

            $results[] = $result;
        }
        return $results;
    }
}
