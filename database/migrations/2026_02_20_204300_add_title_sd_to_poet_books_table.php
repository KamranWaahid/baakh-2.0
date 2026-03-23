<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('poet_books', 'title_sd')) {
            Schema::table('poet_books', function (Blueprint $table) {
                $table->string('title_sd')->nullable()->after('title');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('poet_books', 'title_sd')) {
            Schema::table('poet_books', function (Blueprint $table) {
                $table->dropColumn('title_sd');
            });
        }
    }
};
