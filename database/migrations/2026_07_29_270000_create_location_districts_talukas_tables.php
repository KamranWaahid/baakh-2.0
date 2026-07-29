<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pakistan admin hierarchy under province:
 * province → district → taluka (cities remain under province, optionally linked).
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('location_districts')) {
            Schema::create('location_districts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('province_id')->index();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('province_id')
                    ->references('id')
                    ->on('location_provinces')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('location_district_details')) {
            Schema::create('location_district_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('district_id')->index();
                $table->string('district_name');
                $table->string('lang', 5)->default('sd')->index();
                $table->timestamps();

                $table->unique(['district_id', 'lang'], 'loc_district_details_lang_unique');
                $table->foreign('district_id')
                    ->references('id')
                    ->on('location_districts')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('location_talukas')) {
            Schema::create('location_talukas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('district_id')->index();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('district_id')
                    ->references('id')
                    ->on('location_districts')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('location_taluka_details')) {
            Schema::create('location_taluka_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('taluka_id')->index();
                $table->string('taluka_name');
                $table->string('lang', 5)->default('sd')->index();
                $table->timestamps();

                $table->unique(['taluka_id', 'lang'], 'loc_taluka_details_lang_unique');
                $table->foreign('taluka_id')
                    ->references('id')
                    ->on('location_talukas')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('location_cities')) {
            Schema::table('location_cities', function (Blueprint $table) {
                if (!Schema::hasColumn('location_cities', 'district_id')) {
                    $table->unsignedBigInteger('district_id')->nullable()->index()->after('province_id');
                }
                if (!Schema::hasColumn('location_cities', 'taluka_id')) {
                    $table->unsignedBigInteger('taluka_id')->nullable()->index()->after('district_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('location_cities')) {
            Schema::table('location_cities', function (Blueprint $table) {
                if (Schema::hasColumn('location_cities', 'taluka_id')) {
                    $table->dropColumn('taluka_id');
                }
                if (Schema::hasColumn('location_cities', 'district_id')) {
                    $table->dropColumn('district_id');
                }
            });
        }

        Schema::dropIfExists('location_taluka_details');
        Schema::dropIfExists('location_talukas');
        Schema::dropIfExists('location_district_details');
        Schema::dropIfExists('location_districts');
    }
};
