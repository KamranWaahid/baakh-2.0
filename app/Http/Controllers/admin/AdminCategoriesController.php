<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Languages;
use App\Rules\SlugRule;
use App\Rules\SlugRuleCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class AdminCategoriesController extends Controller
{
    
    public function index()
    {

        $languages = ['sd' => 'Sindhi', 'en' => 'English'];
        $categories = Categories::with(['details:id,cat_id,lang'])->get();
         
        return view('admin.categories.index', compact('categories', 'languages'));
    }


    /**
     * Trashed Items
     */
    public function trashed()
    {
        $languages = ['sd' => 'Sindhi', 'en' => 'English'];
        $categories = Categories::with(['details:id,cat_id,lang'])->onlyTrashed()->get();

        return view('admin.categories.trashed', compact('languages', 'categories'));
    }

    /**
     * Create Category
     */
    public function create()
    {
        $languages = Languages::get();
        $content_styles = ['justified','center','start','end'];
        return view('admin.categories.create', compact('content_styles', 'languages'));
    }


    /**
     * Store a new category
     */
    public function store(Request $request)
    {
        
        $request->validate([
            'slug' => ['required', new SlugRuleCategory()], // Ensure the slug is unique
            'content_style' => 'required|string',
            'cat_name.*' => 'required|string',
            'cat_name_plural.*' => 'required|string',
            'lang.*' => 'required|string'
        ]);
        dd($request->all());

        $category = Categories::create([
            'user_id' => $request->user_id,
            'slug' => $request->slug,
            'content_style' => $request->content_style,
            'gender' => $request->gender,
            'is_featured' => ($request->is_featured) ? 1 : 0
        ]);

        $total = count($request->cat_name);
        for ($i=0; $i < $total; $i++) { 
            $data = [
                'cat_name' => $request->input('cat_name')[$i],
                'cat_name_plural' => $request->input('cat_name_plural')[$i],
                'cat_detail' => $request->input('cat_detail')[$i],
                'lang' => $request->input('lang')[$i],
            ];
            $category->details()->create($data);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update Category
     */
    public function edit($id)
    {
        $category = Categories::with('details')->findOrFail($id);
        $languages = Languages::get();
        $content_styles = ['justified','center','start','end'];
        return view('admin.categories.edit', compact('category', 'languages', 'content_styles'));
    }

    /**
     * Update Category
     */
    public function update(Request $request, $id)
    {
        $category = Categories::findOrFail($id);

        $request->validate([
            'slug' => ['required', new SlugRuleCategory($id)], // Ensure the slug is unique
        ]);

        $category->update([
            'content_style' => $request->content_style,
            'slug' => $request->slug,
            'gender' => $request->gender,
            'is_featured' => ($request->is_featured) ? 1 : 0
        ]);

        $total = count($request->cat_name);
        $category->details()->delete();

        for ($i=0; $i < $total; $i++) { 
            $data = [
                'cat_name' => $request->input('cat_name')[$i],
                'cat_name_plural' => $request->input('cat_name_plural')[$i],
                'cat_detail' => $request->input('cat_detail')[$i],
                'lang' => $request->input('lang')[$i],
            ];
            $category->details()->create($data);
        }



        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Move to trash
     */
    public function destroy(Categories $category)
    {
        $category->delete();
        
        $message = [
            'message' => 'Category moved to trash',
            'type' => 'success'
        ];
        return response()->json($message);
    }

    public function hardDelete($id){
        try {
            $category = Categories::withTrashed()->findOrFail($id);
            
         
            // Delete related detials first
            if ($category->details) {
                foreach ($category->details as $detail) {
                    $detail->forceDelete();
                }
            }

            // Then, force delete the category record
            $category->forceDelete();

            // array for message
            $message = [
                'message' => 'Category and its details are permanently deleted',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Category not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }

    /**
     * Restore the current deleted categroy
     */
    public function restore($id)
    {
        try {
            $category = Categories::withTrashed()->findOrFail($id);
            $category->restore();

            // array for message
            $message = [
                'message' => 'Category restored successfully',
                'type' => 'success',
                'icon' => ''
            ];
    
            return response()->json($message);

        } catch (ModelNotFoundException $e) {
            $message = [
                'message' => 'Category not found',
                'type' => 'error',
                'icon' => ''
            ];
            return response()->json($message);
        }
    }
}
