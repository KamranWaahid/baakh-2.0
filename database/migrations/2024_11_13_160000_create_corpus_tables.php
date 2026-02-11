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
        Schema::create('corpus_sentences', function (Blueprint $table) {
            $table->id();
            $table->text('sentence');
            $table->string('source')->nullable();
            $table->string('category')->nullable();
            $table->json('tokens'); // Array of token IDs
            $table->integer('token_count');
            $table->string('external_id')->nullable()->index();
            $table->timestamps();

            $table->index('source');
            $table->index('category');
        });

        Schema::create('corpus_stats', function (Blueprint $table) {
            $table->id();
            $table->string('word')->index();
            $table->bigInteger('frequency')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corpus_stats');
        Schema::dropIfExists('corpus_sentences');
    }
};
