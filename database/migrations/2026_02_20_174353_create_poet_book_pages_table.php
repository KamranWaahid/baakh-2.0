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
        Schema::create('poet_book_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('poet_books')->onDelete('cascade');
            $table->integer('page_number');
            $table->string('title')->nullable();
            $table->string('type')->default('poetry'); // poetry, information, cover, preface, blank
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->unique(['book_id', 'page_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poet_book_pages');
    }
};
