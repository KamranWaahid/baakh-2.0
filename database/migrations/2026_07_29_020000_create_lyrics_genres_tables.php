<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lyrics_genres', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('visibility')->default(true);
            $table->timestamps();
        });

        Schema::create('lyrics_genre_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lyrics_genre_id');
            $table->string('lang', 10)->default('sd');
            $table->string('name');
            $table->timestamps();

            $table->foreign('lyrics_genre_id')->references('id')->on('lyrics_genres')->onDelete('cascade');
            $table->unique(['lyrics_genre_id', 'lang']);
        });

        Schema::table('lyrics', function (Blueprint $table) {
            $table->unsignedBigInteger('genre_id')->nullable()->after('singer_id');
            $table->foreign('genre_id')->references('id')->on('lyrics_genres')->onDelete('set null');
            $table->index('genre_id');
        });
    }

    public function down(): void
    {
        Schema::table('lyrics', function (Blueprint $table) {
            $table->dropForeign(['genre_id']);
            $table->dropColumn('genre_id');
        });

        Schema::dropIfExists('lyrics_genre_details');
        Schema::dropIfExists('lyrics_genres');
    }
};
