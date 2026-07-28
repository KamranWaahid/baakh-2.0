<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Countries Refactor
        if (!Schema::hasTable('location_country_details')) {
            Schema::create('location_country_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('country_id');
                $table->string('countryName');
                $table->text('countryDesc')->nullable();
                $table->string('lang', 5)->default('sd');
                $table->timestamps();

                $table->foreign('country_id')->references('id')->on('location_countries')->onDelete('cascade');
            });
        }

        if (Schema::hasColumn('location_countries', 'countryName')) {
            $countries = DB::table('location_countries')
                ->select('id', 'countryName', 'countryDesc', 'lang', 'created_at', 'updated_at')
                ->get();

            foreach ($countries as $country) {
                if ($country->countryName === null || $country->countryName === '') {
                    continue;
                }

                DB::table('location_country_details')->updateOrInsert(
                    [
                        'country_id' => $country->id,
                        'lang' => $country->lang ?: 'sd',
                    ],
                    [
                        'countryName' => $country->countryName,
                        'countryDesc' => $country->countryDesc,
                        'created_at' => $country->created_at ?? now(),
                        'updated_at' => $country->updated_at ?? now(),
                    ]
                );
            }

            Schema::table('location_countries', function (Blueprint $table) {
                $table->dropColumn(['countryName', 'countryDesc', 'lang']);
            });
        }

        // 2. Provinces Refactor
        if (!Schema::hasTable('location_province_details')) {
            Schema::create('location_province_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('province_id');
                $table->string('province_name');
                $table->string('lang', 5)->default('sd');
                $table->timestamps();

                $table->foreign('province_id')->references('id')->on('location_provinces')->onDelete('cascade');
            });
        }

        if (Schema::hasColumn('location_provinces', 'province_name')) {
            $provinces = DB::table('location_provinces')
                ->select('id', 'province_name', 'lang', 'created_at', 'updated_at')
                ->get();

            foreach ($provinces as $province) {
                if ($province->province_name === null || $province->province_name === '') {
                    continue;
                }

                DB::table('location_province_details')->updateOrInsert(
                    [
                        'province_id' => $province->id,
                        'lang' => $province->lang ?: 'sd',
                    ],
                    [
                        'province_name' => $province->province_name,
                        'created_at' => $province->created_at ?? now(),
                        'updated_at' => $province->updated_at ?? now(),
                    ]
                );
            }

            Schema::table('location_provinces', function (Blueprint $table) {
                $table->dropColumn(['province_name', 'lang']);
            });
        }

        // 3. Cities Refactor
        if (!Schema::hasTable('location_city_details')) {
            Schema::create('location_city_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('city_id');
                $table->string('city_name');
                $table->string('lang', 5)->default('sd');
                $table->timestamps();

                $table->foreign('city_id')->references('id')->on('location_cities')->onDelete('cascade');
            });
        }

        if (Schema::hasColumn('location_cities', 'city_name')) {
            $cities = DB::table('location_cities')
                ->select('id', 'city_name', 'lang', 'created_at', 'updated_at')
                ->get();

            foreach ($cities as $city) {
                if ($city->city_name === null || $city->city_name === '') {
                    continue;
                }

                DB::table('location_city_details')->updateOrInsert(
                    [
                        'city_id' => $city->id,
                        'lang' => $city->lang ?: 'sd',
                    ],
                    [
                        'city_name' => $city->city_name,
                        'created_at' => $city->created_at ?? now(),
                        'updated_at' => $city->updated_at ?? now(),
                    ]
                );
            }

            Schema::table('location_cities', function (Blueprint $table) {
                $table->dropColumn(['city_name', 'lang']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revert Cities
        if (!Schema::hasColumn('location_cities', 'city_name')) {
            Schema::table('location_cities', function (Blueprint $table) {
                $table->string('city_name')->nullable();
                $table->string('lang')->default('sd');
            });
        }

        if (Schema::hasTable('location_city_details')) {
            foreach (DB::table('location_city_details')->orderBy('id')->get() as $detail) {
                DB::table('location_cities')->where('id', $detail->city_id)->update([
                    'city_name' => $detail->city_name,
                    'lang' => $detail->lang,
                ]);
            }
            Schema::dropIfExists('location_city_details');
        }

        // 2. Revert Provinces
        if (!Schema::hasColumn('location_provinces', 'province_name')) {
            Schema::table('location_provinces', function (Blueprint $table) {
                $table->string('province_name')->nullable();
                $table->string('lang')->default('sd');
            });
        }

        if (Schema::hasTable('location_province_details')) {
            foreach (DB::table('location_province_details')->orderBy('id')->get() as $detail) {
                DB::table('location_provinces')->where('id', $detail->province_id)->update([
                    'province_name' => $detail->province_name,
                    'lang' => $detail->lang,
                ]);
            }
            Schema::dropIfExists('location_province_details');
        }

        // 3. Revert Countries
        if (!Schema::hasColumn('location_countries', 'countryName')) {
            Schema::table('location_countries', function (Blueprint $table) {
                $table->string('countryName')->nullable();
                $table->text('countryDesc')->nullable();
                $table->string('lang')->default('sd');
            });
        }

        if (Schema::hasTable('location_country_details')) {
            foreach (DB::table('location_country_details')->orderBy('id')->get() as $detail) {
                DB::table('location_countries')->where('id', $detail->country_id)->update([
                    'countryName' => $detail->countryName,
                    'countryDesc' => $detail->countryDesc,
                    'lang' => $detail->lang,
                ]);
            }
            Schema::dropIfExists('location_country_details');
        }
    }
};
