<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lyrics_parts', function (Blueprint $table) {
            $table->unsignedBigInteger('source_lyrics_id')->nullable()->after('couplet_id');
            $table->unsignedBigInteger('source_part_id')->nullable()->after('source_lyrics_id');

            $table->foreign('source_lyrics_id')->references('id')->on('lyrics')->onDelete('set null');
            $table->foreign('source_part_id')->references('id')->on('lyrics_parts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('lyrics_parts', function (Blueprint $table) {
            $table->dropForeign(['source_lyrics_id']);
            $table->dropForeign(['source_part_id']);
            $table->dropColumn(['source_lyrics_id', 'source_part_id']);
        });
    }
};
