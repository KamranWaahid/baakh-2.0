<?php

namespace App\Http\Controllers\admin;


use App\Http\Controllers\Controller;
use App\Models\Romanizer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class AdminRomanizerController extends Controller
{
    public function index()
    {
        return view('admin.romanizer.index');
    }

    public function words(){        
        return view('admin.romanizer.words');
    }

    public function with_trashed(){
        return view('admin.romanizer.trashed');
    }

    public function refresh()
    {
        $words  = Romanizer::all();
        $filePath = public_path('vendor/roman-converter/all_words.dic');
        
        $fileHandle = fopen($filePath, 'w');

        
        if ($fileHandle) {
            foreach ($words as $key => $v) {
                $line = $v->word_sd . ":" . $v->word_roman . PHP_EOL;
                fwrite($fileHandle, $line);
            }
            fflush($fileHandle);
            fclose($fileHandle);
        }

        return redirect()->route('admin.romanizer.index')
            ->with('success', 'File successfully updated.');
    }


    public function store(Request $request)
    {
        // Validation and saving logic
        $request->validate([
            'word_sd' => 'required|unique:baakh_roman_words,word_sd,NULL,id',
            'word_roman' => 'required'
        ]);

        Romanizer::create([
            'word_sd' => $request->word_sd,
            'word_roman' => $request->word_roman,
            'user_id' => $request->user_id
        ]);

        return response()->json(['message' => 'added new word']);
    }
 

    public function update(Request $request)
    {
        $id = $request->id;
        $words = Romanizer::findOrFail($id);

        $request->validate([
            'column' => 'required',
            'tvalues' => 'required'
        ]);

        $column = $request->column;
        $value = $request->tvalues;
        $data = array($column => $value);

        if ($words->update($data)) {
            $response['type'] = 'success';
            $response['message'] = 'Word ['.$value.'] has been updated';
        }else{
            $response['type'] = 'error';
            $response['message'] = 'could\'nt updated word ['.$value.']';
        }
        
        return response()->json($response);
    }

    /**
     * Restore Deleted Word
     * 
    */
    public function restore($id){
        try {
            $word = Romanizer::withTrashed()->findOrFail($id);
            $word->restore();

            // array for message
            $message = [
                'message' => 'Word ['.$word->word_sd.'] restored successfully',
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

    public function destroy($id)
    {
        
        $word = Romanizer::findOrFail($id);
        $word->delete();

        if($word->delete()){
            $res['type'] = 'success';
            $res['message'] = 'Word ['.$word->word_sd.', '.$word->word_roman.'] deleted successfully.';
        }else{
            $res['type'] = 'error';
            $res['message'] = 'Word ['.$word->word_sd.', '.$word->word_roman.'] could not deleted.';
        }

        return response()->json($res);
    }

    /**
     * Hard Delete from Database
     * 
    */
    public function hardDelete($id){
        try {
            $word = Romanizer::withTrashed()->findOrFail($id);
            
            // Then, force delete the poetry record
            $word->forceDelete();

            // array for message
            $message = [
                'message' => 'Word has been permanently deleted',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Word not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }


    /**
     * Ajax Methods for Romanizer
     * 
    */
    public function checkWords(Request $request){
        
        $get_text = explode(' ', $request->text);
        $text = array_unique($get_text);
        $remains = array();
        $html = '';

        foreach ($text as $k => $word) { 
            
            $v_Search = str_replace(['،', '’', '‘', '”', '“', '?', '!', '؛', '.', '؟', ' '], '', $word);

            if(!empty($v_Search) && !Romanizer::where('word_sd', $v_Search)->exists()){
                $html .= view('admin.romanizer.form-data', ['word' => $v_Search, 'key' => $k ]);
                $remains[] = $v_Search;
            }
        }

        $totalRemains = count($remains);
        if ($totalRemains > 0) {
            $message = [
                'message' => 'Total '.$totalRemains.' words need to be added',
                'html_content' => $html,
                'type' => 'success'
            ];
        } else {
            $message = [
                'message' => 'All words are available in the database',
                'type' => 'error'
            ];
        }

        return response()->json($message);
    }

    // dataTableWords json data table
    public function dataTableWords(Request $request)
    {
        $columns = ['id', 'word_sd', 'word_roman'];
        $query = Romanizer::select($columns);

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
        }

        $data = DataTables::eloquent($query)
        ->addColumn('actions', function ($row) {
            return '<button type="button" data-id="'.$row->id.'" data-url="'.route('admin.romanizer.destroy', ['id' => $row->id]).'" data-toggle="tooltip" data-placement="top" title="Delete Word" class="btn btn-sm btn-danger btn-delete-word"><i class="fa fa-trash"></i></button>';
        })
        ->rawColumns(['actions'])
        ->toJson();

        return $data;
    }

    // dataTableWords json data table
    public function dataTableWords_trashed(Request $request)
    {
        $columns = ['id', 'word_sd', 'word_roman'];
        $query = Romanizer::onlyTrashed()->select($columns);

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
        }

        $data = DataTables::eloquent($query)
        ->addColumn('actions', function ($row) {
            $html ='<button type="button" data-id="'.$row->id.'" data-url="'.route('admin.romanizer.restore', ['id' => $row->id]).'" data-toggle="tooltip" data-placement="top" title="Rollback Word" class="btn btn-sm btn-info mr-2 btn-rollback-word"><i class="fa fa-undo"></i></button>';
            $html .='<button type="button" data-id="'.$row->id.'" data-url="'.route('admin.romanizer.hard-delete', ['id' => $row->id]).'" data-toggle="tooltip" data-placement="top" title="Delete Word Permanently" class="btn btn-sm btn-danger btn-delete-word"><i class="fa fa-trash"></i></button>';
            return $html;
        })
        ->rawColumns(['actions'])
        ->toJson();

        return $data;
    }

}
