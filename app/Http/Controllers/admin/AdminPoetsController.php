<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Cities;
use App\Models\Languages;
use App\Models\Poets;
use App\Models\PoetsDetail;
use App\Models\Tags;
use App\Rules\SlugRule;
use App\Rules\SlugRulePoet;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPoetsController extends Controller
{


    public function index()
    {
        $poets = Poets::with(['details' => function ($query)  {
            $query->where('poets_detail.lang', 'sd');
        }])->get();
        $breadcrumbs = [
            [
                'label' => 'Home',
                'url' => route('dashboard'),
            ],
            [
                'label' => 'Poets',
                'url' => '#',
                'active' => true,
            ],
        ];
        return view('admin.poets.index', compact('poets', 'breadcrumbs'));
    }

    // for soft deleted items
    public function trashed()
    {
        $poets = Poets::with('details')->onlyTrashed()->get();
        $breadcrumbs = [
            [
                'label' => 'Home',
                'url' => route('dashboard'),
            ],
            [
                'label' => 'Poets',
                'url' => route('admin.poets.index'),
            ],
            [
                'label' => 'Trashed Poets',
                'url' => '#',
                'active' => true,
            ],
        ];
        return view('admin.poets.trashed', compact('poets', 'breadcrumbs'));
    }

    public function create()
    {
        $tags = Tags::where(['type' => 'poets', 'lang' => 'sd'])->get();
        $breadcrumbs = [
            [
                'label' => 'Home',
                'url' => route('dashboard'),
            ],
            [
                'label' => 'Poets',
                'url' => route('admin.poets.index'),
            ],
            [
                'label' => 'Creat Poet Profile',
                'url' => '#',
                'active' => true,
            ],
        ];
        return view('admin.poets.create', compact( 'tags', 'breadcrumbs'));
    }
    

    public function store(Request $request)
    {
        $request->validate([
            'poet_slug' => ['required', new SlugRulePoet()], // Ensure the slug is unique
            'date_of_birth' => 'required',
            'poet_name.*' => 'required|string|min:3',
            'poet_laqab.*' => 'required|string|min:3',
            'lang.*' => 'required',
            'birth_place.*' => 'required',
            'image' => 'required|image|mimes:jpeg,webp,jpg'
        ]);
        

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName(); // Generate a unique name for the image
            $imagePath = 'assets/images/poets/' . $imageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $image->move(public_path('assets/images/poets'), $imageName);
        }

        // Create the poet's basic information
        $poet = Poets::create([
            'poet_slug' => $request->poet_slug,
            'poet_pic' => $imagePath,
            'date_of_birth' => $request->date_of_birth ?? NULL,
            'date_of_death' => $request->date_of_death ?? NULL,
            'poet_tags' => ($request->poet_tags) ? json_encode($request->poet_tags) : NULL,
        ]);

        // count all information
        $count = count($request->input('poet_name'));

        for ($i=0; $i < $count; $i++) {    
           
            $details = [
                'poet_name' => $request->input('poet_name')[$i] ?? NULL,
                'poet_laqab' => $request->input('poet_laqab')[$i] ?? NULL,
                'pen_name' => $request->input('pen_name')[$i] ?? NULL,
                'tagline' => $request->input('tagline')[$i] ?? NULL,
                'poet_bio' => $request->input('poet_bio')[$i] ?? NULL,
                'birth_place' => $request->input('birth_place')[$i] ?? NULL,
                'death_place' => $request->input('death_place')[$i] ?? NULL,
                'lang' => $request->input('lang')[$i] ?? NULL
            ];
        
            // Create and associate the poet's details
            $poet->details()->create($details);
        }


        return redirect()->route('admin.poets.index')
            ->with('success', 'Poet information created successfully.');
    }

    public function edit($id)
    {
        $poet = Poets::findOrFail($id);
        $details = PoetsDetail::with('birthPlace', 'deathPlace')->where('poet_id', $id)->get();
        $languages = Languages::all();
        $tags = Tags::where('type', 'poets')->get();
        $breadcrumbs = [
            [
                'label' => 'Home',
                'url' => route('dashboard'),
            ],
            [
                'label' => 'Poets',
                'url' => route('admin.poets.index'),
            ],
            [
                'label' => 'Edit Poet Profile',
                'url' => '#',
                'active' => true,
            ],
        ];
        return view('admin.poets.edit', compact('poet', 'details',  'languages', 'tags', 'breadcrumbs'));
    }

    /**
     * Update function will update first basic information
     * then it will check the details, then it will delete old details and inser new one
     *
     * @param Request $request
     * @param [type] $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $poet = Poets::findOrFail($id);

        $request->validate([
            'poet_slug' => ['required', new SlugRulePoet($poet->id)], // Ensure the slug is unique
            'poet_pic' => 'image|mimes:jpeg,png,jpg',
            'date_of_birth' => 'required',
            'poet_name.*' => 'required|string|min:3',
            'poet_laqab.*' => 'required|string|min:3',
            'lang.*' => 'required',
            'birth_place.*' => 'required',
            'date_of_death' => 'nullable'
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName(); // Generate a unique name for the image
            $imagePath = 'assets/images/poets/' . $imageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $image->move(public_path('assets/images/poets'), $imageName);
    
            if (file_exists($poet->poet_pic)) {
                unlink($poet->poet_pic);
            }
            $poetsData['image'] = $imagePath;
        }else{
            $imagePath = $poet->poet_pic;
        }
        
        // update the poet's basic information
        $poet->update([
            'poet_slug' => $request->poet_slug,
            'poet_pic' => $imagePath,
            'date_of_birth' => $request->date_of_birth ?? NULL,
            'date_of_death' => $request->date_of_death ?? NULL,
            'poet_tags' => ($request->poet_tags) ? json_encode($request->poet_tags) : NULL,
        ]);


        
        $poet->details()->forceDelete(); // Remove existing information
        $count = count($request->input('poet_name')); // count all new information
        for ($i=0; $i < $count; $i++) {    
           
            $details = [
                'poet_name' => $request->input('poet_name')[$i] ?? NULL,
                'poet_laqab' => $request->input('poet_laqab')[$i] ?? NULL,
                'pen_name' => $request->input('pen_name')[$i] ?? NULL,
                'tagline' => $request->input('tagline')[$i] ?? NULL,
                'poet_bio' => $request->input('poet_bio')[$i] ?? NULL,
                'birth_place' => $request->input('birth_place')[$i] ?? NULL,
                'death_place' => $request->input('death_place')[$i] ?? NULL,
                'lang' => $request->input('lang')[$i] ?? NULL
            ];
        
            // Create and associate the poet's details
            $poet->details()->create($details);
        }
        return redirect()->route('admin.poets.index')
        ->with('success', 'Poet information updated successfully.');
    }

    /**
     * Destroy [Delete] Poetry
     */
    public function destroy($id)
    {
        $poetry = Poets::findOrFail($id);
        $poetry->delete();
        $message = [
            'message' => 'Poet\'s information moved to trash',
            'type' => 'success',
            'icon' => ''
        ];
        return response()->json($message);
    }

    public function hardDelete($id){
        try {
            $poetry = Poets::withTrashed()->findOrFail($id);
            
            // Then, force delete the poetry record
            $poetry->forceDelete();

            // array for message
            $message = [
                'message' => 'Poet\'s information permanently deleted',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Poet not found',
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
            $poet = Poets::findOrFail($id);
            
            $newVisibility = $poet->visibility === 1 ? 0 : 1;

            $poet->update([
                'visibility' => $newVisibility
            ]);

            $message = [
                'message' => 'Poet\'s visibility toggled successfully',
                'type' => 'success',
                'visibility' => $newVisibility
            ];

            return response()->json($message);
        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Poet not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

    public function restore($id){
        try {
            $poet = Poets::withTrashed()->findOrFail($id);
            $poet->restore();

            // array for message
            $message = [
                'message' => 'Poet\'s information restored successfully',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Poets not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

    /**
     * Poets By Language
     */
    public function with_language($lang)
    {
        $poets = PoetsDetail::where('lang', $lang)->get();
        return response()->json($poets);
    }

    public function toggleFeatured($id){
        try {
            $poet = Poets::findOrFail($id);
            
            $newVisibility = $poet->is_featured === 1 ? 0 : 1;

            $poet->update([
                'is_featured' => $newVisibility
            ]);

            $message = [
                'message' => 'Poet\'s featured toggled successfully',
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

    public function ajax_poets(Request $request)
    {
         // Get the search term from the Ajax request
         $searchTerm = urldecode($request->input('term'));
         // Get the requested page number from the Ajax request
         $page = $request->input('page');
 
         // Set the number of results per page
         $perPage = 4; // You can adjust this value as per your requirement
         
         // Calculate the offset based on the requested page number
         $offset = ($page - 1) * $perPage;
         $poets = Poets::with('details')
                    ->whereHas('details', function ($query) use ($searchTerm) {
                        $query->where('poets_detail.poet_laqab', 'like', '%' . $searchTerm . '%');
                        $query->where('poets_detail.lang', 'sd');
                    })
                    ->skip($offset)
                    ->take($perPage)
                    ->get();

            $data = [];
            foreach ($poets as $poet) {
                $data[] = [
                    'id' => $poet->id,
                    'text' => $poet->details->poet_laqab,
                ];
            }
        return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE);
    }



}
