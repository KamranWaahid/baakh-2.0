<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mokhii_page_meta', function (Blueprint $table) {
            $table->json('mokhii_fixes')->nullable()->after('canonical_url');
        });
    }

    public function down(): void
    {
        Schema::table('mokhii_page_meta', function (Blueprint $table) {
            $table->dropColumn('mokhii_fixes');
        });
    }
};
