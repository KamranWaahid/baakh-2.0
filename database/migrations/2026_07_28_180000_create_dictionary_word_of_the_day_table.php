<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dictionary_word_of_the_day', function (Blueprint $table) {
            $table->id();
            $table->date('selection_date')->index();
            $table->foreignId('lemma_id')->constrained('lemmas')->cascadeOnDelete();
            $table->string('status', 20)->default('current')->index(); // current|skipped|completed
            $table->unsignedTinyInteger('priority_score')->nullable();
            $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['selection_date', 'lemma_id']);
            $table->index(['selection_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dictionary_word_of_the_day');
    }
};
