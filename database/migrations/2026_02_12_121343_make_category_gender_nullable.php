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
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('categories', function (Blueprint $table) {
                $table->enum('gender', ['masculine', 'feminine'])->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('categories', function (Blueprint $table) {
                $table->enum('gender', ['masculine', 'feminine'])->nullable(false)->change();
            });
        }
    }
};
