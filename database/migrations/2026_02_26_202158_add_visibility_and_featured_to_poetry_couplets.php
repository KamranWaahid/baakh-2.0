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
        Schema::table('poetry_couplets', function (Blueprint $table) {
            $table->boolean('visibility')->default(true)->after('lang');
            $table->boolean('is_featured')->default(false)->after('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poetry_couplets', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'is_featured']);
        });
    }
};
