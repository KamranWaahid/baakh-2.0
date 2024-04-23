<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Couplets;
use App\Models\Languages;
use App\Models\Poets;
use App\Models\PoetsDetail;
use App\Models\Tags;
use App\Rules\SlugRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class AdminCoupletsController extends Controller
{
    
    public function index()
    {
        $languages = Languages::all();
        
        return view('admin.couplets.index', compact('languages'));
    }

  
    public function trashed()
    {
        $couplets = Couplets::with('poet')->onlyTrashed()->get();
        return view('admin.couplets.trashed', compact('couplets'));
    }

    public function create()
    {
        $tags = Tags::where('lang', 'sd')->get();
        $languages = Languages::all();
        $poets = PoetsDetail::where('lang', 'sd')->get();
        return view('admin.couplets.create',  compact('languages', 'poets', 'tags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'couplet_slug' => ['required', new SlugRule($request->lang, Couplets::class, 'couplet_slug')],
            'poet_id' => 'required',
            'couplet_text.*' => 'required',
            'lang.*' => 'required'
        ]);

        // count all available languages of couplets
        $total = count($request->input('lang'));
        
        // loop all languages and add data
        for ($i=0; $i < $total; $i++) { 
            // Create the poet's basic information
            Couplets::create([
                'couplet_slug' => $request->couplet_slug,
                'couplet_text' => $request->input('couplet_text')[$i],
                'poetry_id' => 0,
                'poet_id' => $request->poet_id,
                'lang' => $request->input('lang')[$i],
                'couplet_tags' => json_encode($request->couplet_tags) ?? NULL
            ]);
        }

        

        return redirect()->route('admin.couplets.index')
            ->with('success', 'Couplet created successfully.');
    }

    /**
     * Edit Poetry
     */
    public function edit($id)
    {
        $couplet = Couplets::findOrFail($id);
        $lang = $couplet->lang;
        $poets = PoetsDetail::where('lang', $lang)->get();

        $tags = Tags::where('lang', $lang)->get();
        $languages = Languages::all();
        return view('admin.couplets.edit', compact('couplet', 'poets',  'languages', 'tags'));
    }

    /**
     * Update Poetry
     */
    public function update(Request $request, $id)
    {
         
        // Validation rules similar to the store method
        $request->validate([
            'couplet_slug' => ['required', new SlugRule($request->lang, Couplets::class, 'couplet_slug', $id)],
            'poet_id' => 'required',
            'couplet_text' => 'required',
            'lang' => 'required'
        ]);
        
 
        $couplet = Couplets::findOrFail($id);

        // Update poetry information
        $couplet->update([
            'couplet_slug' => $request->couplet_slug,
            'couplet_text' => $request->couplet_text,
            'poet_id' => $request->poet_id,
            'lang' => $request->lang,
            'couplet_tags' => json_encode($request->couplet_tags) ?? NULL
        ]);

        return redirect()->route('admin.couplets.index')
        ->with('success', 'Poetry information updated successfully.');
    }

    /**
     * Destroy [Delete] Poetry
     */
    public function destroy($id)
    {
        $couplet = Couplets::findOrFail($id);
        $couplet->delete();
        $message = [
            'message' => 'Couplet moved to trash',
            'type' => 'success',
            'icon' => ''
        ];
        return response()->json($message);
    }

    /**
     * Permanently delete couplet
     */
    public function hardDelete($id)
    {
        try {
            $couplet = Couplets::withTrashed()->findOrFail($id);
          
            // Then, force delete the poetry record
            $couplet->forceDelete();

            // array for message
            $message = [
                'message' => 'Couplets permanently deleted',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Couplets not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

    /**
     * Restore deleted couplet
     */
    public function restore($id)
    {
        try {
            $couplet = Couplets::withTrashed()->findOrFail($id);
            $couplet->restore();

            // array for message
            $message = [
                'message' => 'Couplet restored successfully',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Couplet not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

    /**
     * Data Tables Data for All Couplets
     * dataTableWords json data table
     */
    public function dataTableCouplets(Request $request)
    {
        $lang = $request->input('lang');
        $columns = ['id','couplet_text'];
        
        $query = Couplets::with(['poet.details', 'language'])->where('lang', $lang);

        // Implement search
        
        if ($request->has('search') && !empty($request->search['value'])) {
            $query->where(function ($q) use ($columns, $request) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%' . $request->search['value'] . '%');
                }
            });
        }

        // Implement ordering
        if ($request->has('order')) {
            $column = $columns[$request->order[0]['column']];
            $direction = $request->order[0]['dir'];
            $query->orderBy($column, $direction);
        }else{
            $query->orderBy('id', 'desc');
        }

        $data = DataTables::eloquent($query)
        ->addColumn('couplet_text', function ($row) {
            return nl2br($row->couplet_text);
        })
        ->addColumn('actions', function ($row) {
            $editUrl = route('admin.couplets.edit', ['id' => $row->id]);
            $deleteUrl = route('admin.couplets.destroy', ['id' => $row->id]);

            return '<a href="' . $editUrl . '" class="btn btn-xs btn-warning" data-toggle="tooltip" data-placement="top" title="Update Couplets"><i class="fa fa-edit"></i></a>
                    <button type="button" class="btn btn-xs btn-danger btn-delete-couplet" data-id="' . $row->id . '" data-url="' . $deleteUrl . '" data-toggle="tooltip" data-placement="top" title="Delete Couplets"><i class="fa fa-trash"></i></button>';
        })
        ->rawColumns(['couplet_text','actions'])
        ->toJson();

        return $data;
    }


    /**
     * Check Unque Couplet Slug
     */
    public function checkUniqueSlug(Request $request){
        
        $validator = Validator::make($request->all(), [
            'slug' => 'required|array',
            'slug.*' => 'required|unique:poetry_couplets,couplet_slug',
        ]);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }
    
        // If validation passes, all slugs are unique
        return response()->json(['valid' => true]);
    }
}
