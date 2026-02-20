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
        Schema::create('poet_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poet_id')->constrained('poets')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('title');
            $table->integer('total_pages');

            // Metadata
            $table->string('edition')->nullable();
            $table->string('publisher')->nullable();
            $table->string('published_year')->nullable();
            $table->string('isbn')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('visibility')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['poet_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poet_books');
    }
};
