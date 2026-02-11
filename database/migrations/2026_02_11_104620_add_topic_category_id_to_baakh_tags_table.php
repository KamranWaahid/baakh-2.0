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
        Schema::table('baakh_tags', function (Blueprint $table) {
            $table->foreignId('topic_category_id')->nullable()->after('type')->constrained('topic_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baakh_tags', function (Blueprint $table) {
            $table->dropForeign(['topic_category_id']);
            $table->dropColumn('topic_category_id');
        });
    }
};
