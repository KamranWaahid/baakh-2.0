<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Poet-intended sense for a token in a specific poetry line.
 * Lets editors pin which Baakh Lughat sense applies (and optionally promote it).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lughat_poetry_sense_annotations')) {
            return;
        }

        Schema::create('lughat_poetry_sense_annotations', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->unsignedBigInteger('poetry_id')->index();
            $table->unsignedBigInteger('couplet_id')->nullable()->index();
            $table->unsignedInteger('couplet_index')->default(0); // 0-based among Sindhi couplets
            $table->unsignedInteger('token_index')->default(0);
            $table->string('surface_form');
            $table->string('normalized_form')->index();
            $table->foreignId('lemma_id')->nullable()->constrained('lughat_lemmas')->nullOnDelete();
            $table->foreignId('sense_id')->constrained('lughat_senses')->cascadeOnDelete();
            $table->text('note')->nullable(); // meaning of this word in this line
            $table->boolean('promoted')->default(false); // sense_order shuffled to top
            $table->timestamps();

            $table->unique(
                ['poetry_id', 'couplet_index', 'token_index'],
                'lughat_poetry_sense_ann_token_unique'
            );
            $table->index(['lemma_id', 'poetry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lughat_poetry_sense_annotations');
    }
};
