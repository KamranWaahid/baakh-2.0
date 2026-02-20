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
            $table->foreignId('book_id')->nullable()->constrained('poet_books')->onDelete('set null');
            $table->integer('page_start')->nullable();
            $table->integer('page_end')->nullable();
            $table->index(['book_id', 'page_start']);
        });

        Schema::table('poetry_couplets', function (Blueprint $table) {
            $table->foreignId('book_id')->nullable()->constrained('poet_books')->onDelete('set null');
            $table->integer('page_start')->nullable();
            $table->integer('page_end')->nullable();
            $table->index(['book_id', 'page_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poetry_main', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropColumn(['book_id', 'page_start', 'page_end']);
        });

        Schema::table('poetry_couplets', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropColumn(['book_id', 'page_start', 'page_end']);
        });
    }
};
