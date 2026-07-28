<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('singers', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('singer_pic');
            $table->date('date_of_death')->nullable()->after('date_of_birth');
        });

        Schema::table('singers_detail', function (Blueprint $table) {
            $table->string('singer_laqab')->nullable()->after('singer_name');
            $table->string('tagline')->nullable()->after('singer_laqab');
            $table->string('birth_place')->nullable()->after('tagline');
            $table->string('death_place')->nullable()->after('birth_place');
        });
    }

    public function down(): void
    {
        Schema::table('singers', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth', 'date_of_death']);
        });

        Schema::table('singers_detail', function (Blueprint $table) {
            $table->dropColumn(['singer_laqab', 'tagline', 'birth_place', 'death_place']);
        });
    }
};
