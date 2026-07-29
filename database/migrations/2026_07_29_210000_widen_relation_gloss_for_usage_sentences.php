<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lughat_relations') && Schema::hasColumn('lughat_relations', 'gloss')) {
            Schema::table('lughat_relations', function (Blueprint $table) {
                $table->text('gloss')->nullable()->change();
            });
        }

        if (Schema::hasTable('lemma_relations') && Schema::hasColumn('lemma_relations', 'gloss')) {
            Schema::table('lemma_relations', function (Blueprint $table) {
                $table->text('gloss')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lughat_relations') && Schema::hasColumn('lughat_relations', 'gloss')) {
            Schema::table('lughat_relations', function (Blueprint $table) {
                $table->string('gloss')->nullable()->change();
            });
        }

        if (Schema::hasTable('lemma_relations') && Schema::hasColumn('lemma_relations', 'gloss')) {
            Schema::table('lemma_relations', function (Blueprint $table) {
                $table->string('gloss')->nullable()->change();
            });
        }
    }
};
