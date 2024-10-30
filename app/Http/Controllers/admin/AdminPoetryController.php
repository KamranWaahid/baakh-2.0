<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\CategoryDetails;
use App\Models\Couplets;
use App\Models\Languages;
use App\Models\Poetry;
use App\Models\PoetryTranslations;
use App\Models\Poets;
use App\Models\Tags;
use App\Rules\SlugRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AdminPoetryController extends Controller
{
    
    
    public function index()
    {
        
        
        $poets = Poets::with(['details' => function($query) {
            $query->where('lang', 'sd');
        }])->get();

        $categories = CategoryDetails::where('lang', 'sd')->get();
        return view('admin.poetry.index', compact('categories', 'poets'));
    }

    /**
     * Poet function is used to filter poetry by poet
     *
     * @param [slug of poet] $slug 
     */
    public function poet($slug)
    {
        $poetry = Poetry::with('poet', 'category', 'language')
                        ->whereHas('poet', function ($query) use ($slug) {
                            $query->whereNull('deleted_at')->where('poet_slug', $slug);
                        })
                        ->get();
        $languages = Languages::all();
        return view('admin.poetry.index', compact('poetry', 'languages'));
    }

  
    /**
     * Trashed poetry
     */
    public function trashed()
    {
        $poetry = Poetry::with('poet', 'category')->onlyTrashed()->get();
        return view('admin.poetry.trashed', compact('poetry'));
    }

    public function create()
    {
        $poets = Poets::with('details')->get();
        $tags = Tags::where('lang','sd')->get();
        $categories = Categories::all();
        $languages = Languages::all();
        $content_styles = ['justified','center','start','end'];
        return view('admin.poetry.create',  compact('poets', 'categories', 'languages', 'tags', 'content_styles'));
    }
 

    public function store_old(Request $request)
    {
       // dd($request->all());
        
        $request->validate([
            'poetry_slug' => ['required', new SlugRule($request->lang, Poetry::class, 'poetry_slug')],
            'poetry_title' => 'required',
            'poet_id' => 'required',
            'content_style' => 'required',
            'lang' => 'required',
            'category_id' => 'required'
        ]);

        $poetId = $request->poet_id;
        $userId = Auth::user()->id;
        // Create the poet's basic information
        $poetry = Poetry::create([
            'user_id' => $userId,
            'poetry_slug' => $request->poetry_slug,
            'poetry_title' => $request->poetry_title,
            'poet_id' => $poetId,
            'lang' => $request->lang,
            'category_id' => $request->category_id,
            'content_style' => $request->content_style,
            'poetry_tags' => json_encode($request->poetry_tags) ?? null,
            'poetry_info' => $request->poetry_info ?? null,
        ]);

        // count all information
        $count = count($request->input('couplet_text'));
        for ($i = 0; $i < $count; $i++) {
            $details = [
                'couplet_slug' => $request->input('couplet_slug')[$i] ?? null,
                'couplet_text' => $request->input('couplet_text')[$i] ?? null,
                'poet_id' => $request->poet_id,
                'lang' => $request->lang
            ];

            if (!empty($request->input('couplet_tags')[$i])) {
                $details['couplet_tags'] = json_encode($request->input('couplet_tags'));
            }

            $poetry->couplets()->create($details);
        }
        


        return redirect()->route('admin.poetry.index')
            ->with('success', 'Poetry information created successfully.');
    }

    public function store(Request $request) {
        // add main information with Poetry::create()
        // get lastCreatedID and then add poetry's Title, Source and Info with PoetryTranslations::create()
        // then add Couplets with lastCreatedID using [default lang is = sd] Couplets::create()

        $main_poetry = [
            'poet_id' => $request->input('poet_id'),
            'category_id' => $request->input('category_id'),
            'poetry_slug' => $request->input('poetry_slug'),
            'poetry_tags' => $request->input('poetry_tags'),
            'visibility' => $request->input('is_visible'),
            'is_featured' => $request->input('is_featured'),
            'content_style' => $request->input('content_style')
        ];
        /**
         *   'poet_id', 'category_id',  'user_id',  'poetry_slug', 'poetry_tags', 'visibility', 'is_featured', 'content_style',
         */
        $created = Poetry::create($main_poetry);

        if($created) {
            // add translations PoetryTranslations:: [poetry_id]
            $created->translations()->create([
                'title' => $request->input('poetry_title'),
                'info' => $request->input('poetry_info'),
                'source' => $request->input('source'),
                'lang' => 'sd',
            ]);

            // loop to include couplets
            $coupletTexts = $request->input('couplet_text');
            $coupletSlugs = $request->input('couplet_slug');

            foreach ($coupletTexts as $index => $coupletText) {
                $created->couplets()->create([
                    'couplet_text' => $coupletText,
                    'poet_id' => $request->input('poet_id'),
                    'couplet_slug' => $coupletSlugs[$index]
                ]);
            }
            $url = route('admin.poetry.add-translation', ['id' => $created->id, 'language' => 'en']);
            return response()->json(['type' => 'success', 'message' => 'Poetry added', 'route' => $url]);
        }

        return response()->json(['type' => 'error', 'message' => 'Error while adding poetry']);
    }


    /*
    | Show Poetry
    */
    public function show($id)
    {
        $poetry = Poetry::with('poet', 'category', 'couplets')->find($id);
        return view('admin.poetry.show', compact('poetry'));
    }

    /**
     * Edit Poetry
     */
    public function edit($id)
    {
        $poetry = Poetry::with('poet', 'category', 'all_couplets')->find($id);
        $poets = Poets::with('details')->get();

        $tags = Tags::all();
        $categories = Categories::with('detail')->get();
        $languages = Languages::all();
        $content_styles = ['justified','center','start','end'];
        return view('admin.poetry.edit', compact('poetry', 'poets',  'categories', 'languages', 'tags', 'content_styles'));
    }

   

    /**
     * Update Poetry
     */
    public function update(Request $request, $id)
    {
        $poetry = Poetry::findOrFail($id);

        // Validation rules similar to the store method
        $request->validate([
            'poetry_slug' => ['required', new SlugRule($request->lang, Poetry::class, 'poetry_slug', $id)],
            'poetry_title' => 'required',
            'poet_id' => 'required',
            'content_style' => 'required',
            'lang' => 'required',
            'category_id' => 'required'
        ]);

        // Update poetry information
        $poetry->update([
            'poetry_slug' => $request->poetry_slug,
            'poetry_title' => $request->poetry_title,
            'poet_id' => $request->poet_id,
            'lang' => $request->lang,
            'category_id' => $request->category_id,
            'content_style' => $request->content_style,
            'poetry_tags' => json_encode($request->poetry_tags) ?? null,
            'poetry_info' => $request->poetry_info ?? null,
        ]);

        
        
        $count = count($request->input('couplet_slug')); // count new Couplets
        if($count > 0){
            $poetry->couplets()->delete(); // Delete existing associated Couplets
            for ($i = 0; $i < $count; $i++) {
                $details = [
                    'couplet_slug' => $request->input('couplet_slug')[$i] ?? null,
                    'couplet_text' => $request->input('couplet_text')[$i] ?? null,
                    'poet_id' => $request->poet_id,
                    'lang' => $request->lang
                ];
                $poetry->couplets()->create($details);
            }
        }
        

        return redirect()->route('admin.poetry.index')
        ->with('success', 'Poetry information updated successfully.');
    }

    /**
     * Destroy [Delete] Poetry
     */
    public function destroy($id)
    {
        $poetry = Poetry::findOrFail($id);
        $poetry->delete();
        $message = [
            'message' => 'Poetry information moved to trash',
            'type' => 'success',
            'icon' => ''
        ];
        return response()->json($message);
    }

    public function hardDelete($id){
        try {
            $poetry = Poetry::withTrashed()->findOrFail($id);
            
            // Delete related couplets first
            if ($poetry->all_couplets) {
                foreach ($poetry->all_couplets as $detail) {
                    $detail->forceDelete();
                }
            }

            // Then, force delete the poetry record
            $poetry->forceDelete();

            // array for message
            $message = [
                'message' => 'Poetry information permanently deleted',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Poetry not found',
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
            $poetry = Poetry::findOrFail($id);
            
            $newVisibility = $poetry->visibility === 1 ? 0 : 1;

            $poetry->update([
                'visibility' => $newVisibility
            ]);

            $message = [
                'message' => 'Poetry visibility toggled successfully',
                'type' => 'success',
                'visibility' => $newVisibility
            ];

            return response()->json($message);
        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Poetry not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

    /**
     * Add or Remove Poetry from Featured
     */
    public function toggleFeatured($id) {

        $poetry = Poetry::findOrFail($id);
        
        if($poetry->is_featured == 1) {
            $new_featured = 0;
            $msg = 'Poetry was removed from Featured list';
        }else{
            $new_featured = 1;
            $msg = 'Poetry was Added to Featured list';
        }

        $poetry->update([
            'is_featured' => $new_featured
        ]);

        $message = [
            'message' => $msg,
            'type' => 'success',
            'featured' => $new_featured
        ];

        return response()->json($message);
    }

    /**
     * Restore Poetry from Deleted list
     */
    public function restore($id){
        try {
            $poetry = Poetry::withTrashed()->findOrFail($id);
            $poetry->restore();

            // array for message
            $message = [
                'message' => 'Poetry information restored successfully',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Poetry not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

    /**
     * Check Slug of the poetry by language
     * @param slug = input.poetry_slug 
     */
    public function check_slug(Request $request)
    {
        $poetry = Poetry::where(['poetry_slug' => $request->slug])->withTrashed()->get();
        if(count($poetry) == 0)
        {
            $message = [
                'message' => 'You can add poetry',
                'type' => 'success',
                'icon' => ''
            ];
        }else{
            $message = [
                'message' => 'Sorry! slug already exists',
                'type' => 'error',
                'icon' => ''
            ];
        }
        
        return response()->json($message);
    }

    /**
     * Data Tables Data for All Poetry
     * dataTableWords json data table
     */
    public function dataTablePoetry(Request $request)
    {
        $cat_id = $request->input('cat_id');
        $poet = $request->input('poet_id');
        $columns = ['id'];

       
        

        $query = Poetry::with([
            'info' => function($query){
                $query->select('poetry_id', 'title')->where('lang', 'sd');
            },
            'poet_details' => function ($query){
                $query->select('poet_id', 'poet_laqab')->where('lang', 'sd');
            },
            'user' => function ($q) { // belongsTo relation with User model
                $q->select('id', 'name', 'name_sd', 'role'); // showing NULL 
            },
            'category.detail' => function ($cat_query) {
                $cat_query->select('cat_id', 'cat_name')->where('lang', 'sd');
            },
            'translations' => function($query){
                $query->with(['language' => function ($lang_query) {
                    $lang_query->select('lang_code', 'lang_title');
                }])->select('poetry_id', 'lang');
            },
        ]);
       
        
        // Implement search
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = '%' . $request->search['value'] . '%';

            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('info.title', 'like', $searchValue)
                  ->orWhereHas('poet_details', function ($q) use ($searchValue) {
                      $q->where('poet_laqab', 'like', $searchValue);
                  });
            });
        }

        
        // Implement filtering by language and poet_id
        if ($poet != 0 || $poet != '0') {
            $query->where('poet_id',$poet);
        }

        if($cat_id !=0 || $cat_id != '0') {
            $query->where('category_id', $cat_id);
        }

        // Implement ordering based on request
        if ($request->has('order')) {
            $column = $columns[$request->order[0]['column']];
            $direction = $request->order[0]['dir'];
            $query->orderBy($column, $direction);
        }

        $data = DataTables::eloquent($query)

        // for user's information who uploaded or edited
        ->addColumn('user_info', function ($row) {
            return $row->user?->name .'<br>' . $row->created_at;
        })
        // for URL and titles
        ->addColumn('poetry_title', function ($row) {
            $url = route('poetry.with-slug', ['category' => $row->category->slug, 'slug' => $row->poetry_slug]);
            $html = '<a href="'.$url.'" class="text-linked" target="_blank">'.$row->info->title.'</a>';
            return $html;
        })

        // for information
        ->addColumn('information', function ($row) {
            $_lang_info = '';
            $availableLanguages = Languages::pluck('lang_code')->toArray();
            $missingLanguages = array_diff($availableLanguages, $row->translations->pluck('lang')->toArray());
            if (!empty($missingLanguages)) {
                foreach ($missingLanguages as $langCode) {
                    $_lang_info .= '<a href="'.route('admin.poetry.add-translation', ['id' => $row->id, 'language' => $langCode]).'"><span class="badge bg-warning p-1 mr-1 rounded"><i class="fa fa-plus mr-1"></i>' . $langCode . '</span></a>'; //
                }
            }
            foreach ($row->translations as $translation) {
               
                $btn_langs_title = $translation->language?->lang_title;
                $_lang_info .= '<a href="'.route('admin.poetry.edit-translation', ['id' => $row->id, 'language' => $translation->language?->lang_code]).'"><span class="badge bg-success p-1 mr-1 rounded"><i class="fa fa-edit mr-1"></i>' . $btn_langs_title . '</span></a>'; //
                //$_lang_info .='<span class="badge bg-success p-1 mr-1 rounded"><i class="fa fa-globe mr-1"></i>'.$btn_langs_title.'</span>';
            }
        
            $_lang_info .= '<span class="badge bg-info p-1 mr-1 rounded"><i class="fa fa-folder mr-1"></i>'.$row->category->detail?->cat_name.'</span>';
            return $_lang_info;
        })
        

        // for poets names
        ->addColumn('poets', function ($row) {
            return $row->poet_details?->poet_laqab;
        })

        // for buttons
        ->addColumn('actions', function ($row) {
            $mediaCreateUrl = route('admin.media.create', $row->id);
            $editUrl = route('admin.poetry.edit', $row->id);
            $toggleVisibilityUrl = route('admin.poetry.toggle-visibility', ['id' => $row->id]);
            $toggleFeaturedUrl = route('admin.poetry.toggle-featured', ['id' => $row->id]);
            $deleteUrl = route('admin.poetry.destroy', ['id' => $row->id]);
    
            return '<a href="' . $mediaCreateUrl . '" class="btn btn-xs btn-success mr-1" data-toggle="tooltip" data-placement="top" title="Poetry Media"><i class="fa fa-video"></i></a>' .
                   '<a href="' . $editUrl . '" class="btn btn-xs btn-warning mr-1" data-toggle="tooltip" data-placement="top" title="Update Poetry"><i class="fa fa-edit"></i></a>' .
                   '<button type="button" data-id="' . $row->id . '" data-url="' . $toggleVisibilityUrl . '" data-toggle="tooltip" data-placement="top" title="' . ($row->visibility == 1 ? 'Hide' : 'Show') . ' Poetry" class="btn btn-xs btn-info mr-1 btn-visible-poetry"><i class="fa fa-' . ($row->visibility == 1 ? 'eye' : 'eye-slash') . '"></i></button>' .
                   '<button type="button" data-id="' . $row->id . '" data-url="' . $toggleFeaturedUrl .'" data-toggle="tooltip" data-placement="top" title="' .( $row->is_featured == 1 ? 'Hide' : 'Show' ). ' From Featured" class="btn btn-xs btn-default btn-featured-poetry"><i class="' . ($row->is_featured == 1 ? 'fa fa-star text-warning' : 'fa fa-star') . '"></i></button>' .
                   '<button type="button" data-id="' . $row->id . '" data-url="' . $deleteUrl . '" data-toggle="tooltip" data-placement="top" title="Delete Poetry" class="btn btn-xs btn-danger mr-1 btn-delete-poetry"><i class="fa fa-trash"></i></button>';
        })
        ->rawColumns(['actions', 'information', 'user_info', 'poetry_title', 'poets'])
        ->toJson();

        return $data;
    }



}
