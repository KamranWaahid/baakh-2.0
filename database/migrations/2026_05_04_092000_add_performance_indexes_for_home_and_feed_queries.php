<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexIfMissing('poetry_main', ['visibility', 'created_at'], 'poetry_main_visibility_created_idx');
        $this->addIndexIfMissing('poetry_main', ['visibility', 'is_featured', 'created_at'], 'poetry_main_vis_feat_created_idx');
        $this->addIndexIfMissing('poetry_main', 'poetry_slug', 'poetry_main_slug_idx');
        $this->addIndexIfMissing('poetry_main', ['category_id', 'visibility', 'poet_id'], 'poetry_main_cat_vis_poet_idx');

        $this->addIndexIfMissing('poetry_couplets', ['lang', 'poet_id'], 'poetry_couplets_lang_poet_idx');
        $this->addIndexIfMissing('poetry_couplets', 'couplet_slug', 'poetry_couplets_slug_idx');

        $this->addIndexIfMissing('poets', ['visibility', 'deleted_at'], 'poets_visibility_deleted_idx');
        $this->addIndexIfMissing('poets', ['date_of_birth', 'date_of_death'], 'poets_birth_death_idx');

        $this->addIndexIfMissing('poets_detail', ['poet_id', 'lang'], 'poets_detail_poet_lang_idx');
        $this->addIndexIfMissing('likes_dislikes', ['user_id', 'likable_type', 'likable_id'], 'likes_user_type_likable_idx');

        $this->addIndexIfMissing('baakh_tags', ['lang', 'type'], 'baakh_tags_lang_type_idx');
        $this->addIndexIfMissing('baakh_tags', ['lang', 'slug'], 'baakh_tags_lang_slug_idx');

        $this->addIndexIfMissing('today_modules', ['table_name', 'date_today'], 'today_modules_table_date_idx');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('poetry_main', 'poetry_main_visibility_created_idx');
        $this->dropIndexIfExists('poetry_main', 'poetry_main_vis_feat_created_idx');
        $this->dropIndexIfExists('poetry_main', 'poetry_main_slug_idx');
        $this->dropIndexIfExists('poetry_main', 'poetry_main_cat_vis_poet_idx');

        $this->dropIndexIfExists('poetry_couplets', 'poetry_couplets_lang_poet_idx');
        $this->dropIndexIfExists('poetry_couplets', 'poetry_couplets_slug_idx');

        $this->dropIndexIfExists('poets', 'poets_visibility_deleted_idx');
        $this->dropIndexIfExists('poets', 'poets_birth_death_idx');

        $this->dropIndexIfExists('poets_detail', 'poets_detail_poet_lang_idx');
        $this->dropIndexIfExists('likes_dislikes', 'likes_user_type_likable_idx');
        $this->dropIndexIfExists('baakh_tags', 'baakh_tags_lang_type_idx');
        $this->dropIndexIfExists('baakh_tags', 'baakh_tags_lang_slug_idx');
        $this->dropIndexIfExists('today_modules', 'today_modules_table_date_idx');
    }

    private function addIndexIfMissing(string $tableName, array|string $columns, string $indexName): void
    {
        $columnList = is_array($columns) ? $columns : [$columns];
        if (!$this->columnsExist($tableName, $columnList)) {
            return;
        }

        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $databaseName = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$databaseName, $tableName, $indexName]
        );

        return (bool) $result;
    }

    private function columnsExist(string $tableName, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                return false;
            }
        }

        return true;
    }
};
