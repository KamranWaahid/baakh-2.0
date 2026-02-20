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
        Schema::create('poet_book_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->unique()->constrained('poet_books')->onDelete('cascade');
            $table->integer('last_page')->default(0);
            $table->unsignedBigInteger('last_poetry_id')->nullable();
            $table->unsignedBigInteger('last_couplet_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poet_book_progress');
    }
};
