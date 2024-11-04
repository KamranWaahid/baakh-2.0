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
        $languages = Languages::all();
        
        $db_categories = Categories::with(['details.language'])
        ->get();
         
        $categories = [];
        foreach ($db_categories as $category) {
            $categoryData = [];
            $categoryData['id'] = $category->id;
            $categoryData['slug'] = $category->slug;
            $categoryData['is_featured'] = $category->is_featured;
            $categoryData['content_style'] = $category->content_style;
            $categoryData['deleted_at'] = $category->deleted_at;

            // Get the first cat_name from category_details
            $firstCatName = $category->details->first()->cat_name ?? '';

            $categoryData['cat_name'] = $firstCatName;

            $availableLanguages = $category->details->pluck('language.lang_title', 'language.lang_code')->unique()->toArray();

            $categoryData['languages'] = $availableLanguages;
        
            $categories[] = $categoryData;
        }

        $categories = array_map(function ($category) {
            return (object)$category;
        }, $categories);
        
        $content_styles = ['justified','center','start','end'];
        return view('admin.categories.index', compact('languages', 'categories', 'content_styles'));
    }


    /**
     * Trashed Items
     */
    public function trashed()
    {
        $languages = Languages::all();
        
        $db_categories = Categories::with(['details.language'])
        ->onlyTrashed()->get();
         
        $categories = [];
        foreach ($db_categories as $category) {
            $categoryData = [];
            $categoryData['id'] = $category->id;
            $categoryData['slug'] = $category->slug;
            $categoryData['is_featured'] = $category->is_featured;
            $categoryData['content_style'] = $category->content_style;
            $categoryData['deleted_at'] = $category->deleted_at;

            // Get the first cat_name from category_details
            $firstCatName = $category->details->first()->cat_name ?? '';

            $categoryData['cat_name'] = $firstCatName;

            $availableLanguages = $category->details->pluck('language.lang_title', 'language.lang_code')->unique()->toArray();

            $categoryData['languages'] = $availableLanguages;
        
            $categories[] = $categoryData;
        }

        $categories = array_map(function ($category) {
            return (object)$category;
        }, $categories);

        return view('admin.categories.trashed', compact('languages', 'categories'));
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


    public function store(Request $request)
    {
        $request->validate([
            'slug' => ['required', new SlugRuleCategory()], // Ensure the slug is unique
        ]);


        $category = Categories::create([
            'user_id' => $request->user_id,
            'slug' => $request->slug,
            'content_style' => $request->content_style,
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

    public function edit($id)
    {
        $category = Categories::with('details')->findOrFail($id);
        $languages = Languages::all();
        $content_styles = ['justified','center','start','end'];
        return view('admin.categories.edit', compact('category', 'languages', 'content_styles'));
    }

    public function update(Request $request, $id)
    {
        $category = Categories::findOrFail($id);

        $request->validate([
            'slug' => ['required', new SlugRuleCategory($id)], // Ensure the slug is unique
        ]);

        $category->update([
            'content_style' => $request->content_style,
            'slug' => $request->slug,
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

    public function destroy($id)
    {
        $category = Categories::findOrFail($id);
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

}
