<?php 
namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait BaakhLikedTrait
{
/**
     * Private method to check if item is liked or not
     * first parameter is the Model name
     * second parameter is Model's ID
     */
    public function isLikedItem($type, $slug)
    {
        /**
         * @var App\Models\User $user
         */
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

?>