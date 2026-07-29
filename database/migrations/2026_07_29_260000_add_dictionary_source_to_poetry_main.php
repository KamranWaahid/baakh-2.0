<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('poetry_main')) {
            return;
        }

        Schema::table('poetry_main', function (Blueprint $table) {
            if (!Schema::hasColumn('poetry_main', 'dictionary_source')) {
                $table->string('dictionary_source', 30)
                    ->default('general')
                    ->after('content_style')
                    ->index();
                // general = Open Lexicon / site dictionary
                // lughat = Baakh Lughat poetic dictionary
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('poetry_main') && Schema::hasColumn('poetry_main', 'dictionary_source')) {
            Schema::table('poetry_main', function (Blueprint $table) {
                $table->dropColumn('dictionary_source');
            });
        }
    }
};
