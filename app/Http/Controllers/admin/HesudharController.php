<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaakhHesudhar;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class HesudharController extends Controller
{
    public function index()
    {
        return view('admin.hesudhar.index');
    }

   
    public function with_trashed(){
        return view('admin.hesudhar.trashed');
    }

    public function refresh()
    {
        $words  = BaakhHesudhar::all();
        $filePath = public_path('vendor/hesudhar/words.dic');
        
        $fileHandle = fopen($filePath, 'w');

        
        if ($fileHandle) {
            foreach ($words as $key => $v) {
                $line = $v->word . ":" . $v->correct . PHP_EOL;
                fwrite($fileHandle, $line);
            }
            fflush($fileHandle);
            fclose($fileHandle);
        }

        return redirect()->route('admin.hesudhar')
            ->with('success', 'File successfully updated.');
    }


    public function store(Request $request)
    {
        // Validation and saving logic
        $request->validate([
            'word' => 'required|unique:baakh_hesudhars,word,NULL,id',
            'correct' => 'required'
        ]);

        BaakhHesudhar::create([
            'word' => $request->word,
            'correct' => $request->correct
        ]);

        return response()->json(['message' => 'added new word']);
    }
 

    public function update(Request $request)
    {
        $id = $request->id;
        $words = BaakhHesudhar::findOrFail($id);

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
            $word = BaakhHesudhar::withTrashed()->findOrFail($id);
            $word->restore();

            // array for message
            $message = [
                'message' => 'Word ['.$word->correct.'] restored successfully',
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
        
        $word = BaakhHesudhar::findOrFail($id);
        $word->delete();

        if($word->delete()){
            $res['type'] = 'success';
            $res['message'] = 'Word ['.$word->word.', '.$word->correct.'] deleted successfully.';
        }else{
            $res['type'] = 'error';
            $res['message'] = 'Word ['.$word->word.', '.$word->correct.'] could not deleted.';
        }

        return response()->json($res);
    }

    /**
     * Hard Delete from Database
     * 
    */
    public function hardDelete($id){
        try {
            $word = BaakhHesudhar::withTrashed()->findOrFail($id);
            
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


     

    // dataTableWords json data table
    public function dataTableWords(Request $request)
    {
        $columns = ['id', 'word', 'correct'];
        $query = BaakhHesudhar::select($columns);

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
            return '<button type="button" data-id="'.$row->id.'" data-url="'.route('admin.hesudhar.destroy', ['id' => $row->id]).'" data-toggle="tooltip" data-placement="top" title="Delete Word" class="btn btn-sm btn-danger btn-delete-word"><i class="fa fa-trash"></i></button>';
        })
        ->rawColumns(['actions'])
        ->toJson();

        return $data;
    }

    // dataTableWords json data table
    public function dataTableWords_trashed(Request $request)
    {
        $columns = ['id', 'word', 'correct'];
        $query = BaakhHesudhar::onlyTrashed()->select($columns);

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
            $html ='<button type="button" data-id="'.$row->id.'" data-url="'.route('admin.hesudhar.restore', ['id' => $row->id]).'" data-toggle="tooltip" data-placement="top" title="Rollback Word" class="btn btn-sm btn-info mr-2 btn-rollback-word"><i class="fa fa-undo"></i></button>';
            $html .='<button type="button" data-id="'.$row->id.'" data-url="'.route('admin.hesudhar.hard-delete', ['id' => $row->id]).'" data-toggle="tooltip" data-placement="top" title="Delete Word Permanently" class="btn btn-sm btn-danger btn-delete-word"><i class="fa fa-trash"></i></button>';
            return $html;
        })
        ->rawColumns(['actions'])
        ->toJson();

        return $data;
    }
}
