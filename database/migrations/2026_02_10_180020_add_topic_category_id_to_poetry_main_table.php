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
        Schema::table('poetry_main', function (Blueprint $table) {
            $table->unsignedBigInteger('topic_category_id')->nullable()->after('category_id');
            $table->foreign('topic_category_id')->references('id')->on('topic_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poetry_main', function (Blueprint $table) {
            $table->dropForeign(['topic_category_id']);
            $table->dropColumn('topic_category_id');
        });
    }
};
