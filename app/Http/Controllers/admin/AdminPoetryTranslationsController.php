<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Couplets;
use App\Models\Poetry;
use App\Models\PoetryTranslations;
use App\Models\Tags;
use Illuminate\Http\Request;

class AdminPoetryTranslationsController extends Controller
{

     /**
     * Edit Translation method to update as per language
     */
    public function createTranslations($id, $lang = 'en') {

        $info = PoetryTranslations::where(['poetry_id' => $id, 'lang' => 'sd'])->first();
        $for_language = $lang;
       
        $tags = Tags::where('lang', 'sd')->get();
        $couplets = Couplets::where(['poetry_id' =>  $id, 'lang' => 'sd'])->get();
         
        return view('admin.poetry.create_translation', compact('info', 'for_language', 'couplets', 'tags'));
    }

    /**
     * Edit Page Translation of poetry
     */
    public function editTranslations($id, $lang = 'en') {
        $info = PoetryTranslations::where(['poetry_id' => $id, 'lang' => 'sd'])->first();
        $info_tr = PoetryTranslations::where(['poetry_id' => $id, 'lang' => $lang])->first();
        $for_language = $lang;
        
        $couplets = Couplets::where(['poetry_id' =>  $id, 'lang' => 'sd'])->get();
        $couplets_tr = Couplets::where(['poetry_id' => $id, 'lang' => $lang])->get();
        $tags = Tags::where('lang', 'sd')->get();

        if(!$info_tr) {
            return to_route('admin.poetry.add-translation', ['id' => $id, 'language' => $for_language])->with('message', 'Add Translations of this poetry first');
        }
        
        return view('admin.poetry.edit_translation', compact('info', 'info_tr', 'for_language', 'couplets', 'couplets_tr', 'tags'));
    }

    // store translations of info
    public function addInfo(Request $request) {
        $save = PoetryTranslations::create($request->all());
        if($save) {
            return response()->json(['type' => 'success' , 'message' => 'Poetry trnaslation added successfully']);
        }
        return response()->json(['type' => 'error' , 'message' => 'Unable to add poetry translation']);
    }

    public function updateInfo(Request $request, $id, $lang = 'en') {
        
        $infoId = $request->info_id;
        $info = PoetryTranslations::where(['id' => $infoId, 'lang' => $lang])->first();

        if($info) {
            $info->title = $request->title;
            $info->source = $request->source;
            $info->info = $request->info;
            $info->save();
            return response()->json(['type' => 'success' , 'message' => 'Poetry trnaslation updated successfully']);
        }else{
            return response()->json(['type' => 'error' , 'message' => 'Can not find poetry translation to update']);
        }
    }

    // Add Translation of couplets
    public function addCoupletsTranslation(Request $request, $id, $lang) {
        
        $poetry = Poetry::findOrFail($id);
        $coupletTexts = $request->input('couplet_text');
        $coupletSlugs = $request->input('couplet_slug');
        $coupletTags = $request->input('couplet_tags');

        foreach ($coupletTexts as $index => $coupletText) {
            $poetry->couplets()->create([
                'couplet_text' => $coupletText,
                'poet_id' => $request->input('poet_id'),
                'lang' => $request->input('lang'),
                'couplet_slug' => $coupletSlugs[$index],
                'couplet_tags' => $coupletTags[$index] ?? null
            ]);
        }
        $url = route('admin.poetry.index');
        return response()->json(['type' => 'success', 'message' => 'couplet translations added', 'route' => $url]);
    }

    public function updateCoupletsTranslation(Request $request, $id, $lang) {
        // new couplets
        $coupletTexts = $request->input('couplet_text');
        $coupletSlugs = $request->input('couplet_slug');
        $coupletTags = $request->input('couplet_tags');
        $coupletIds = $request->input('couplet_ids');

        foreach ($coupletTexts as $index => $coupletText) {
            $coupletId = $coupletIds[$index];
            $existingCouplet = Couplets::find($coupletId);
            if ($existingCouplet) {
                $existingCouplet->update([
                    'couplet_text' => $coupletText,
                    'couplet_slug' => $coupletSlugs[$index],
                    'couplet_tags' => $coupletTags[$index]
                ]);
            }else{
                return response()->json(['type' => 'error', 'message' => 'soemthing is wrong with couplets, IDs not available']);
            }
        }
        $url = route('admin.poetry.index');
        return response()->json(['type' => 'success', 'message' => 'couplet translations updated', 'route' => $url]);
    }


}
