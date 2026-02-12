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
        Schema::table('location_countries', function (Blueprint $table) {
            $table->foreign('capital_city')->references('id')->on('location_cities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_countries', function (Blueprint $table) {
            $table->dropForeign(['capital_city']);
        });
    }
};
