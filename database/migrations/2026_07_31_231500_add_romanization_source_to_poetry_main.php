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
            if (!Schema::hasColumn('poetry_main', 'romanization_source')) {
                $table->string('romanization_source', 30)
                    ->default('legacy')
                    ->after('dictionary_source')
                    ->index();
                // legacy = old poetry Romanizer snapshot
                // baakh_lughat = generated from Baakh Lughat transliterations
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('poetry_main') && Schema::hasColumn('poetry_main', 'romanization_source')) {
            Schema::table('poetry_main', function (Blueprint $table) {
                $table->dropColumn('romanization_source');
            });
        }
    }
};
