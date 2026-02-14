<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('languages', 'is_default')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('lang_folder');
            });
        }

        // Set Sindhi as default
        DB::table('languages')->where('lang_code', 'sd')->update(['is_default' => true]);
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
