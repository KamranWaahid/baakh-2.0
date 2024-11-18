<?php 
namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait SQLiteTrait
{
    /**
     * Update Poetry Main
     * when Poets model or PoetDetails model is updated
     * update single poetry having poetry_id in SQLite table
     * Model: UnifiedPoetry
     */
    protected function updatePoetry($model)
    {
        $poetry_data = DB::select(
            'SELECT pm.id as poetry_id, pm.category_id, pm.poet_id, pm.poetry_slug,  pt.title,  pt.lang  
             FROM  poetry_main pm 
             INNER JOIN  poetry_translations pt  ON pt.poetry_id = pm.id
             WHERE pm.id = :main_id', 
             ['main_id' => $model]
        );
        DB::connection('sqlite')->enableQueryLog();

        if(empty($poetry_data)) {
            Log::warning('No poetry data found for model ID '. $model);
            return;
        }

        foreach ($poetry_data as $data) {
            DB::connection('sqlite')->table('unified_poetry')->updateOrInsert
            (
                ['poetry_id' => $data->poetry_id, 'lang' => $data->lang],
                [
                    'category_id' => $data->category_id,
                    'poet_id' => $data->poet_id,
                    'poetry_slug' => $data->poetry_slug,
                    'title' => $this->cleanText($data->title),
                    'title_original' => $data->title,
                ]
            );
    
        }
    }

    /**
     * Update Poet
     * update a poet with its detail having poet_id in SQLite database
     * Model : UnifiedPoets {Poets, PoetDetails}
     */
    protected function updatePoet($poetId)
    {
        $poet_data = DB::select(
            'SELECT p.id as poet_id, p.poet_slug , pd.poet_name, pd.poet_laqab, pd.lang 
             FROM poets p 
             INNER JOIN poets_detail pd ON pd.poet_id=p.id WHERE p.id = :min_id', 
             ['min_id' => $poetId]
        );
        
        if(empty($poet_data)) {
            Log::warning('No poet data found for model ID '. $poetId);
            return;
        }
 
        foreach ($poet_data as $data) {
            DB::connection('sqlite')->table('unified_poets')->updateOrInsert
            (
                ['poet_id' => $data->poet_id, 'lang' => $data->lang],
                [
                    'poet_slug' => $data->poet_slug,
                    'poet_name' => $this->cleanText($data->poet_name),
                    'poet_laqab' => $this->cleanText($data->poet_laqab)
                ]
            );
        }
    }

    /**
     * Update Tags
     * update single tag information having id in SQLite database
     * Model : UnifiedTags
     */
    protected function updateTag($tagId)
    {
        $tag_data = DB::select('SELECT `id`, `tag`, `slug`, `type`, `lang` FROM baakh_tags WHERE id = :main_id', ['main_id' => $tagId]);
       
        if(empty($tag_data)) {
            Log::warning('No Tag data found for model ID '. $tagId);
            return;
        }

        foreach ($tag_data as $data) {
            DB::connection('sqlite')->table('unified_tags')->updateOrInsert
            (
                ['id' => $data->id, 'lang' => $data->lang],
                [
                    'tag' => $data->tag,
                    'slug' => $data->slug,
                    'type' => $data->type,
                ]
            );
        }
    }

    /**
     * Update Couplet
     * update a single couplet having couplet_id in SQLite database
     * Model : UnifiedCouplets
     */
    protected function updateCouplet($coupletId)
    {
        $couplets_data = DB::select(
            'SELECT id as couplet_id, poet_id, poetry_id, couplet_slug, couplet_text, lang  
            FROM poetry_couplets WHERE id = :main_id', ['main_id' => $coupletId]);

        if(empty($couplets_data)) {
            Log::warning('No couplet data found for model ID '. $coupletId);
            return;
        }
 
        foreach ($couplets_data as $data) {
            DB::connection('sqlite')->table('unified_couplets')->updateOrInsert
            (
                ['couplet_id' => $data->couplet_id, 'lang' => $data->lang],
                [
                    'poet_id' => $data->poet_id,
                    'poetry_id' => $data->poetry_id,
                    'couplet_slug' => $data->couplet_slug,
                    'couplet_text' => $this->cleanText($data->couplet_text),
                ]
            );
        }
    }

    /**
     * Update Category
     * update a category informaiton, alogn with its translation having category_id in SQLite database
     * Model : UnifiedCategories
     */
    protected function updateCategory($categoryId)
    {
        $categories = DB::select(
            'SELECT c.id as category_id, c.slug, c.gender, cd.cat_name, cd.cat_name_plural, cd.lang 
             FROM categories c 
             INNER JOIN category_details cd ON cd.cat_id=c.id WHERE c.id = :main_id', ['main_id', $categoryId]);
        
        if(empty($categories)) {
            Log::warning('No Category data found for model ID '. $categoryId);
        }

        foreach ($categories as $data) {
            DB::connection('sqlite')->table('unified_categories')->updateOrInsert
            (
                ['category_id' => $data->category_id, 'lang' => $data->lang],
                [
                    'slug' => $data->slug,
                    'gender' => $data->gender,
                    'cat_name' => $data->cat_name,
                    'cat_name_plural' => $data->cat_name_plural,
                ]
            );
        }
    }


    /**
     * Clear content
     */
    protected function cleanText($text){
        $garbadge = ['َ', 'ِ', 'ُ', 'ّ', '،', '.'];
        $cleanGarbage = str_replace($garbadge, '', $text);
        $cleanText = preg_replace('/[^a-zA-Z0-9\s\p{Arabic}]/u', '', $cleanGarbage);
        return $cleanText;
    }
}
