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
        // 1. Countries Refactor
        Schema::table('location_countries', function (Blueprint $table) {
            $table->dropColumn(['countryName', 'countryDesc', 'lang']);
        });

        Schema::create('location_country_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_id');
            $table->string('countryName');
            $table->text('countryDesc')->nullable();
            $table->string('lang', 5)->default('sd');
            $table->timestamps();

            $table->foreign('country_id')->references('id')->on('location_countries')->onDelete('cascade');
        });

        // 2. Provinces Refactor
        Schema::table('location_provinces', function (Blueprint $table) {
            $table->dropColumn(['province_name', 'lang']);
        });

        Schema::create('location_province_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('province_id');
            $table->string('province_name');
            $table->string('lang', 5)->default('sd');
            $table->timestamps();

            $table->foreign('province_id')->references('id')->on('location_provinces')->onDelete('cascade');
        });

        // 3. Cities Refactor
        Schema::table('location_cities', function (Blueprint $table) {
            $table->dropColumn(['city_name', 'lang']);
        });

        Schema::create('location_city_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('city_id');
            $table->string('city_name');
            $table->string('lang', 5)->default('sd');
            $table->timestamps();

            $table->foreign('city_id')->references('id')->on('location_cities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revert Cities
        Schema::dropIfExists('location_city_details');
        Schema::table('location_cities', function (Blueprint $table) {
            $table->string('city_name')->nullable(); // nullable as data is lost
            $table->string('lang')->default('sd');
        });

        // 2. Revert Provinces
        Schema::dropIfExists('location_province_details');
        Schema::table('location_provinces', function (Blueprint $table) {
            $table->string('province_name')->nullable();
            $table->string('lang')->default('sd');
        });

        // 3. Revert Countries
        Schema::dropIfExists('location_country_details');
        Schema::table('location_countries', function (Blueprint $table) {
            $table->string('countryName')->nullable();
            $table->string('countryDesc')->nullable();
            $table->string('lang')->default('sd');
        });
    }
};
