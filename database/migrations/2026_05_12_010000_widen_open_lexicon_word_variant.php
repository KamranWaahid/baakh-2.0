<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('senses', 'word_variant')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE senses MODIFY word_variant TEXT NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('senses', 'word_variant')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE senses MODIFY word_variant VARCHAR(255) NULL');
        }
    }
};
