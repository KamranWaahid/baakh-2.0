<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('lemma_variants') || !Schema::hasColumn('lemma_variants', 'type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE lemma_variants MODIFY type VARCHAR(50) NOT NULL DEFAULT 'dialectal'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE lemma_variants ALTER COLUMN type TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE lemma_variants ALTER COLUMN type SET DEFAULT 'dialectal'");
            DB::statement("ALTER TABLE lemma_variants ALTER COLUMN type SET NOT NULL");
        }
    }

    public function down(): void
    {
        // Keep the widened column so production diacritic/spelling variants are not lost.
    }
};
