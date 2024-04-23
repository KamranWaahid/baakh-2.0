<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Languages;
use App\Models\Tags;
use App\Rules\SlugRule;
use App\Rules\UniqueSlugForLanguage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdminTagsController extends Controller
{
    public function index()
    {
        $languages = Languages::all();
        return view('admin.tags.index', compact('languages'));
    }

    public function with_trashed(){

        $tags = Tags::onlyTrashed()->get();
        return view('admin.tags.trashed', compact('tags'));
    }


    public function create()
    {
        $languages = Languages::all();
        $types = ['poetry', 'poets', 'occation', 'tasviri', 'bundle'];
        return view('admin.tags.create', compact('languages', 'types'));
    }


    public function store(Request $request)
    {
        // Validation and saving logic
        $request->validate([
            'slug' => ['required', new SlugRule($request->lang, Tags::class)],
            'type' => 'required',
            'tag.*' => 'required',
            'lang.*' => 'required',
        ]);

        $total = count($request->input('tag'));
        for ($i=0; $i < $total; $i++) { 
            Tags::create([
                'slug' => $request->slug,
                'tag' => $request->input('tag')[$i],
                'lang' => $request->input('lang')[$i],
                'type' => $request->type
            ]);    
        }

        
        return redirect()->route('admin.tags.index')
        ->with('success', 'added new tags.');
    }


    public function edit($id)
    {
        $tag = Tags::findOrFail($id);
        if(is_null($tag))
        {
            return to_route('admin.tags.index')->with('error', 'Desired tag is not available');
        }
        $data = Tags::with('language')->where('slug', $tag->slug)->get();
        $languages = Languages::all();
        $types = ['poetry', 'poets', 'occation', 'tasviri', 'bundle'];
        return view('admin.tags.edit', compact('data', 'languages', 'types'));
    }
 

    public function update(Request $request)
    {
        $first_id = $request->input('id')[0];
        $request->validate([
            'slug' => ['required', new SlugRule($request->lang, Tags::class, 'slug', $first_id)],
            'type' => 'required',
            'tag.*' => 'required',
            'lang.*' => 'required',
        ]);

        $total = count($request->input('id'));
        for ($i=0; $i < $total; $i++) { 
            $item = Tags::findOrFail($request->input('id')[$i]);
            if(!is_null($item))
            {
                $item->update([
                    'slug' => $request->slug,
                    'tag' => $request->input('tag')[$i],
                    'lang' => $request->input('lang')[$i],
                    'type' => $request->type
                ]);
            }  
        }

        
        return redirect()->route('admin.tags.index')
        ->with('success', 'added new tags.');
    }

    /**
     * Restore Deleted Word
     * 
    */
    public function restore($id){
        try {
            $word = Tags::withTrashed()->findOrFail($id);
            $word->restore();

            // array for message
            $message = [
                'message' => 'Tag ['.$word->tag.'] restored successfully',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Tag not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

    public function destroy($id)
    {
        
        $word = Tags::findOrFail($id);
        $word->delete();

        if($word->delete()){
            $res['type'] = 'success';
            $res['message'] = 'Tag ['.$word->tag.', '.$word->slug.'] deleted successfully.';
        }else{
            $res['type'] = 'error';
            $res['message'] = 'Tag ['.$word->tag.', '.$word->slug.'] could not deleted.';
        }

        return response()->json($res);
    }

    /**
     * Hard Delete from Database
     * 
    */
    public function hardDelete($id){
        try {
            $word = Tags::withTrashed()->findOrFail($id);
            
            // Then, force delete the poetry record
            $word->forceDelete();

            // array for message
            $message = [
                'message' => 'Tag has been permanently deleted',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Tag not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }


    /**
     * JSON tags by language
     */
    public function with_language($lang)
    {
        $tags = Tags::where(['lang' =>  $lang, 'type' => 'poetry'])->get();
        return response()->json($tags);
    }

    /**
     * Data Table fetch all items
     */
    public function allTagsDataTable(Request $request)
    {
        $lang = $request->lang;
        
        $columns = ['id', 'tag', 'slug', 'type'];

        $query = Tags::where('lang', $lang);

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = '%' . $request->search['value'] . '%';
            $query->Where('tag', 'like', $searchValue);
            $query->orWhere('slug', 'like', $searchValue);
            $query->orWhere('type', 'like', $searchValue);
        }

        // Implement ordering
        if ($request->has('order')) {
            $column = $columns[$request->order[0]['column']];
            $direction = $request->order[0]['dir'];
            $query->where('lang', $lang)
                ->orderBy($column, $direction);
        } else {
            $query->where('lang', $lang)
                ->orderBy('id', 'desc');
        }

        
        $data = DataTables::eloquent($query)
        ->addColumn('actions', function ($row) {
            $editUrl = route('admin.tags.edit', $row->id);
            $deleteUrl = route('admin.tags.destroy', ['id' => $row->id]);
    
            return '<a href="' . $editUrl . '" class="btn btn-xs btn-warning mr-1" data-toggle="tooltip" data-placement="top" title="Update Tags"><i class="fa fa-edit"></i></a>' .
                   '<button type="button" data-id="' . $row->id . '" data-url="' . $deleteUrl . '" data-toggle="tooltip" data-placement="top" title="Delete Tag" class="btn btn-xs btn-danger mr-1 btn-delete-tag"><i class="fa fa-trash"></i></button>';
        })
        ->rawColumns(['actions'])
        ->toJson();

        return $data;

    }


}
