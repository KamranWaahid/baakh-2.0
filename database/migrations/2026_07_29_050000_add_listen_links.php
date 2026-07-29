<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lyrics', function (Blueprint $table) {
            $table->json('listen_links')->nullable()->after('music_type');
        });

        Schema::table('singers', function (Blueprint $table) {
            $table->json('listen_links')->nullable()->after('is_featured');
        });

        Schema::table('bands', function (Blueprint $table) {
            $table->json('listen_links')->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('lyrics', function (Blueprint $table) {
            $table->dropColumn('listen_links');
        });

        Schema::table('singers', function (Blueprint $table) {
            $table->dropColumn('listen_links');
        });

        Schema::table('bands', function (Blueprint $table) {
            $table->dropColumn('listen_links');
        });
    }
};
