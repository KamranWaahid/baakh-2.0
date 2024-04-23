<?php

namespace App\Http\Controllers;

use App\Models\Languages;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public $lang;
    protected $lang_dir;
    protected $languages;
    
    public function __construct()
    {
        // Language detection logic
        $this->lang = (request('lang')) ? request('lang') : 'sd';
        $this->languages = $this->siteLanguages();
        $this->setLocale($this->lang);
        $this->shareCommonData();
    }

    protected function setLocale($lang)
    {
         
        // Check if the provided language is supported
         
        $supportedLanguages = $this->languages->pluck('lang_code')->toArray();

        if (empty($lang) || !in_array($lang, $supportedLanguages)) {
            // Set a default language if the provided language is not supported or not provided
            $lang = 'sd';
            
            $this->lang_dir = 'rtl';
        }
        App::setLocale($lang);
        $this->lang = $lang;
        // get language direction of selected language
        $this->lang_dir = Languages::where('lang_code', $lang)->value('lang_dir');

        
    }

    protected function shareCommonData()
    {
        // Use view()->share() to share data with all views
        view()->share('langDir', $this->lang_dir);
        view()->share('siteLanguages', $this->languages);

    }

    protected function siteLanguages() {
        return Languages::all();
    }
  

    /**
     * Private method to check if item is liked or not
     * first parameter is the Model name
     * second parameter is Model's ID
     */
    public function isLiked($type, $slug)
    {
        $user = Auth::user();
        $table_name = ''; // Initialize the table name variable

        switch ($type) {
            case 'Poetry':
                $table_name = 'poetry_main'; // poetry_slug
                $slug_column = 'poetry_slug';
                break;
            case 'Bundles':
                $table_name = 'poetry_bundles'; // slug
                $slug_column = 'slug';
                break;
            case 'Poets':
                $table_name = 'poets'; // poet_slug
                $slug_column = 'poet_slug';
                break;
            case 'Couplets':
                $table_name = 'poetry_couplets'; // couplet_slug
                $slug_column = 'couplet_slug';
                break;
            case 'Tags':
                $table_name = 'baakh_tags'; // slug
                $slug_column = 'slug';
                break;
        }

        if (!$user || empty($table_name)) {
            $liked = '';
        } else {
            $existingLike = $user->likesDislikes()
                ->where('likable_type', $type)
                ->join($table_name, function ($join) use ($slug, $table_name, $slug_column) {
                    $join->on('likes_dislikes.likable_id', '=', "$table_name.id")
                        ->where("$slug_column", $slug);
                })->first();

            $liked = $existingLike ? '-fill text-baakh' : '';
        }

        return $liked;
    }
 
}
