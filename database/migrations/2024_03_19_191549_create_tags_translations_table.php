<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tags_translations', function (Blueprint $table) {
            $table->unsignedBigInteger('tag_id');
            $table->string('lang_code', 20);
            $table->string('title', 255);
            $table->text('detail');
            $table->foreign('tag_id')->references('id')->on('baakh_tags')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baakh_tags_translations');
    }
};
