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
        // 1. Topic Categories Multi-language
        Schema::create('topic_category_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_category_id')->constrained('topic_categories')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('name');
            $table->timestamps();

            $table->unique(['topic_category_id', 'lang']);
        });

        Schema::table('topic_categories', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        // 2. Tags Multi-language
        Schema::create('baakh_tag_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('baakh_tags')->onDelete('cascade');
            $table->string('lang', 5);
            $table->string('name');
            $table->timestamps();

            $table->unique(['tag_id', 'lang']);
        });

        Schema::table('baakh_tags', function (Blueprint $table) {
            $table->dropColumn(['tag', 'lang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baakh_tags', function (Blueprint $table) {
            $table->string('tag')->nullable();
            $table->string('lang', 5)->default('en');
        });

        Schema::dropIfExists('baakh_tag_details');

        Schema::table('topic_categories', function (Blueprint $table) {
            $table->string('name')->nullable();
        });

        Schema::dropIfExists('topic_category_details');
    }
};
