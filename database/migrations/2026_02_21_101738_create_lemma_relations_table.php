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
        Schema::create('lemma_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $table->enum('relation_type', ['synonym', 'antonym', 'hypernym']);
            $table->string('related_word');
            $table->foreignId('related_lemma_id')->nullable()->constrained('lemmas')->nullOnDelete();
            $table->timestamps();

            $table->index(['lemma_id', 'relation_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lemma_relations');
    }
};
