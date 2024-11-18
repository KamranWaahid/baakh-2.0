<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poetry;
use App\Models\Search\UnifiedPoetry;
use App\Models\Search\UnifiedPoets;
use Illuminate\Support\Facades\DB;

class SqliteController extends Controller
{
    public function index()
    {
        $this->touchSQlite();
        return view('admin.sqlite.index');
    }

    public function touchSqlite()
    {
        $sqlitePath = storage_path('app/baakh.sqlite');

        if (!file_exists($sqlitePath)) {
            file_put_contents($sqlitePath, '');

            $this->createTablesIfNotExists();
        }
    }

    /**
     * Create Tables
     * unified_poetry [id, category, poetry_slug, poet_laqab, title, lang ]
     * unified_poets [id, poet_slug, poet_name, poet_laqab, lang ]
     * unified_couplets [id, couplet_id, poet_laqab, couplet_slug, couplet_text, couplet_tags, lang, created_at, updated_at]
     * unified_tags [id, tag, slug, lang]
     */
    public function createTablesIfNotExists()
    {
        $connection = DB::connection('sqlite');
        // Check and create `unified_poetry` table
        if (!$connection->getSchemaBuilder()->hasTable('unified_poetry')) {
            $this->createPoetryTable($connection);
        }

        // Check and create `unified_poets` table
        if (!$connection->getSchemaBuilder()->hasTable('unified_poets')) {
            $this->createPoetsTable($connection);
        }

        // Check and create `unified_couplets` table
        if (!$connection->getSchemaBuilder()->hasTable('unified_couplets')) {
            $this->createCoupletsTable($connection);
        }

        // Check and create `unified_tags` table
        if (!$connection->getSchemaBuilder()->hasTable('unified_tags')) {
            $this->createTagsTable($connection);
        }
    }

    /**
     * Generate Tables
     */
    public function generateTable($tableName)
    {
        $response = [];
        switch ($tableName) {
            case 'poetry':
                $this->generatePoetry();
                $response = ['error' => false, 'message' => 'ReCreated Poetry table along with fresh data'];
                break;
            case 'poets':
                $this->generatePoets();
                $response = ['error' => false, 'message' => 'ReCreated Poets table along with fresh data'];
                break;
            case 'tags':
                $this->generateTags();
                $response = ['error' => false, 'message' => 'ReCreated Tags table along with fresh data'];
                break;
            case 'couplets':
                $this->generateCouplets();
                $response = ['error' => false, 'message' => 'ReCreated Couplets table along with fresh data'];
                break;
            case 'categories':
                $this->generateCategories();
                $response = ['error' => false, 'message' => 'ReCreated Categories table along with fresh data'];
                break;
            
            default:
                $response = ['error' => true, 'message' => 'Required table '.$tableName.' not found'];
                break;
        }
       return $response;
    }

    /**
     * Regenrate Poetry
     */
    public function generatePoetry($recreate = true, $min_id = 0)
    {
        $connection = DB::connection('sqlite');

        if($recreate) {
            if ($connection->getSchemaBuilder()->hasTable('unified_poetry')) {
                $connection->getSchemaBuilder()->dropIfExists('unified_poetry'); // drop previous table
           }
           $this->createPoetryTable($connection); // re-create table
        }

        $poetry_data = DB::select(
            'SELECT pm.id as poetry_id, pm.category_id, pm.poet_id, pm.poetry_slug,  pt.title,  pt.lang  
             FROM  poetry_main pm 
             INNER JOIN  poetry_translations pt  ON pt.poetry_id = pm.id
             WHERE pm.id > :min_id', 
             ['min_id' => $min_id]
        );
        
        $insertData = array_map(function ($value) {
            return [
                'poetry_id' => $value->poetry_id,
                'category_id' => $value->category_id,
                'poet_id' => $value->poet_id,
                'poetry_slug' => $value->poetry_slug,
                'title' => $this->cleanText($value->title),
                'lang' => $value->lang,
            ];
        }, $poetry_data);
        
        $connection->table('unified_poetry')->insert($insertData);

    }

    /**
     * Regenrate Poets
     */
    protected function generatePoets($recreate = true, $min_id = 0){
        $connection = DB::connection('sqlite');

        if($recreate) {
            if ($connection->getSchemaBuilder()->hasTable('unified_poets')) {
                $connection->getSchemaBuilder()->dropIfExists('unified_poets'); // drop previous table
                $this->createPoetsTable($connection); // create poets table
            }
        }

        $poet_data = DB::select('SELECT p.id as poet_id, p.poet_slug , pd.poet_name, pd.poet_laqab, pd.lang FROM poets p INNER JOIN poets_detail pd ON pd.poet_id=p.id WHERE p.id > :min_id ORDER BY p.id ASC', ['min_id' => $min_id]);
        $insertData = array_map(function ($value) {
            return [
                'poet_id' => $value->poet_id,
                'poet_slug' => $value->poet_slug,
                'poet_name' => $this->cleanText($value->poet_name),
                'poet_laqab' => $this->cleanText($value->poet_laqab),
                'lang' => $value->lang,
            ];
        }, $poet_data);

        $connection->table('unified_poets')->insert($insertData);
    }

    /**
     * Generate Couplets
     */
    protected function generateCouplets($recreate = true, $min_id = 0) {
        $connection = DB::connection('sqlite');
        if($recreate) {
            if ($connection->getSchemaBuilder()->hasTable('unified_couplets')) {
                $connection->getSchemaBuilder()->dropIfExists('unified_couplets'); // drop previous table
            }
            $this->createCoupletsTable($connection);
        }
        

        $poet_data = DB::select('SELECT id as couplet_id, poet_id, poetry_id, couplet_slug, couplet_text, lang  FROM poetry_couplets WHERE id > :min_id', ['min_id' => $min_id]);
        $insertData = array_map(function ($value) {
            return [
                'couplet_id' => $value->couplet_id,
                'poet_id' => $value->poet_id,
                'poetry_id' => $value->poetry_id,
                'couplet_slug' => $value->couplet_slug,
                'couplet_text' => $this->cleanText($value->couplet_text),
                'lang' => $value->lang,
            ];
        }, $poet_data);

        $connection->table('unified_couplets')->insert($insertData);
    }

    protected function generateTags($recreate = true, $min_id = 0) {
        $connection = DB::connection('sqlite');

        if($recreate) {
            if ($connection->getSchemaBuilder()->hasTable('unified_tags')) {
                $connection->getSchemaBuilder()->dropIfExists('unified_tags'); // drop previous table
            }
            $this->createTagsTable($connection);
        }

        $tag_data = DB::select('SELECT `id`, `tag`, `slug`, `type`, `lang` FROM baakh_tags WHERE id > :min_id', ['min_id' => $min_id]);
        $insertData = array_map(function ($value) {
            return [
                'id' => $value->id,
                'tag' => $value->tag,
                'slug' => $value->slug,
                'type' => $value->type,
                'lang' => $value->lang,
            ];
        }, $tag_data);

        $connection->table('unified_tags')->insert($insertData);
    }

    protected function generateCategories($recreate = true, $min_id = 0) {
        $connection = DB::connection('sqlite');

        if($recreate) {
            if ($connection->getSchemaBuilder()->hasTable('unified_categories')) {
                $connection->getSchemaBuilder()->dropIfExists('unified_categories'); // drop previous table
            }
            $this->createCategoriesTable($connection);
        }
        

        $categories = DB::select('SELECT c.id as category_id, c.slug, c.gender, cd.cat_name, cd.cat_name_plural, cd.lang FROM categories c INNER JOIN category_details cd ON cd.cat_id=c.id WHERE c.id > :min_id', ['min_id', $min_id]);
        $insertData = array_map(function ($value) {
            return [
                'category_id' => $value->category_id,
                'slug' => $value->slug,
                'gender' => $value->gender,
                'cat_name' => $value->cat_name,
                'cat_name_plural' => $value->cat_name_plural,
                'lang' => $value->lang,
            ];
        }, $categories);

        $connection->table('unified_categories')->insert($insertData);
    }

    

    /**
     * Sync
     */
    public function syncDataBase()
    {
        $connection = DB::connection('sqlite');
        $this->syncPoetryTable($connection);
        $this->syncCategoriesTable($connection);
        $this->syncCoupletsTable($connection);
        $this->syncPoetsTable($connection);
        $this->syncTagsTable($connection);

        return response()->json(['error' => false, 'message' => 'Synced all table\'s data']);
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

    /**
     * Tables Creation protected functions
     */
    protected function createPoetsTable($connection) {
        $connection->getSchemaBuilder()->create('unified_poets', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->string('poet_slug')->nullable();
            $table->string('poet_name')->nullable();
            $table->string('poet_laqab')->nullable();
            $table->string('lang')->nullable();
            $table->timestamps();

            $table->index('poet_name', 'poet_name_index');
            $table->index('poet_laqab', 'poet_laqab_index');
        });
    }

    protected function createPoetryTable($connection) {
        $connection->getSchemaBuilder()->create('unified_poetry', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poetry_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->string('title')->nullable();
            $table->string('poetry_slug')->nullable();
            $table->string('lang')->nullable();
            $table->timestamps();

            $table->index('poetry_id');
            $table->index('category_id');
            $table->index('poet_id');
        });
    }

    protected function createCoupletsTable($connection) {
        $connection->getSchemaBuilder()->create('unified_couplets', function ($table) {
            $table->id();
            $table->unsignedBigInteger('couplet_id')->nullable();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->unsignedBigInteger('poetry_id')->nullable();

            $table->string('couplet_slug')->nullable();
            $table->text('couplet_text')->nullable();
            $table->string('lang')->nullable();
            $table->timestamps();

            $table->index('couplet_id');
            $table->index('poet_id');
            $table->index('poetry_id');
        });
    }

    protected function createTagsTable($connection) {
        $connection->getSchemaBuilder()->create('unified_tags', function ($table) {
            $table->id();
            $table->string('tag')->nullable();
            $table->string('type')->nullable();
            $table->string('slug')->nullable();
            $table->string('lang')->nullable();
            $table->timestamps();
        });
    }

    protected function createCategoriesTable($connection) {
        $connection->getSchemaBuilder()->create('unified_categories', function ($table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('slug')->nullable();
            $table->string('gender')->nullable();
            $table->string('cat_name')->nullable();
            $table->string('cat_name_plural')->nullable();
            $table->string('lang')->nullable();
            $table->timestamps();

            $table->index('category_id');
            $table->index('cat_name');
        });
    }

    /**
     * Sync database tables
     */
    protected function syncPoetryTable($connection) {
        if ($connection->getSchemaBuilder()->hasTable('unified_poetry')) {
            $maxId = $connection->table('unified_poetry')->max('poetry_id'); 
            $this->generatePoetry(false, $maxId); // add new records 
        }else{
            $this->generatePoetry(); // re-create table and populate data
        }
    }

    protected function syncPoetsTable($connection) {
        if ($connection->getSchemaBuilder()->hasTable('unified_poets')) {
            $maxId = $connection->table('unified_poets')->max('poet_id'); 
            $this->generatePoets(false, $maxId); // add new records 
        }else{
            $this->generatePoets(); // re-create table and populate data
        }
    }
    
    protected function syncCoupletsTable($connection) {
        if ($connection->getSchemaBuilder()->hasTable('unified_couplets')) {
            $maxId = $connection->table('unified_couplets')->max('couplet_id'); 
            $this->generateCouplets(false, $maxId); // add new records 
        }else{
            $this->generateCouplets(); // re-create table and populate data
        }
    }
    
    protected function syncTagsTable($connection) {
        if ($connection->getSchemaBuilder()->hasTable('unified_tags')) {
            $maxId = $connection->table('unified_tags')->max('id'); 
            $this->generateTags(false, $maxId); // add new records 
        }else{
            $this->generateTags(); // re-create table and populate data
        }
    }
    
    protected function syncCategoriesTable($connection) {
        if ($connection->getSchemaBuilder()->hasTable('unified_categories')) {
            $maxId = $connection->table('unified_categories')->max('category_id'); 
            $this->generateCategories(false, $maxId); // add new records 
        }else{
            $this->generateCategories(); // re-create table and populate data
        }
    }
}
