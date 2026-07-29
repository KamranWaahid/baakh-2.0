<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multiword poetic expressions (izafat, collocations, metaphors, etc.)
 * alongside individual lemmas — e.g. جامِ محبت = جام + محبت.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('lughat_expressions')) {
            Schema::create('lughat_expressions', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 64)->nullable()->unique();
                $table->string('expression')->index();
                $table->string('normalized_expression')->index();
                $table->string('compact_search_key')->nullable()->index();
                $table->string('romanization')->nullable()->index();
                $table->string('expression_type', 40)->default('izafat')->index();
                // izafat|compound|collocation|idiom|metaphor|fixed_phrase|formulaic_phrase|reduplicative|name_or_title|other
                $table->text('definition_sd')->nullable();
                $table->text('definition_en')->nullable();
                $table->string('literal_gloss')->nullable();
                $table->text('poetic_gloss')->nullable();
                $table->string('register', 40)->nullable()->index(); // poetic|literary|colloquial|…
                $table->string('status', 30)->default('pending')->index(); // pending|approved|rejected
                $table->unsignedTinyInteger('confidence')->nullable();
                $table->string('review_status', 30)->default('unreviewed')->index();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->unique(['normalized_expression', 'expression_type'], 'lughat_expressions_norm_type_unique');
            });
        }

        if (!Schema::hasTable('lughat_expression_components')) {
            Schema::create('lughat_expression_components', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 64)->nullable()->unique();
                $table->foreignId('expression_id')->constrained('lughat_expressions')->cascadeOnDelete();
                $table->unsignedSmallInteger('position')->default(1);
                $table->foreignId('lemma_id')->nullable()->constrained('lughat_lemmas')->nullOnDelete();
                $table->foreignId('word_form_id')->nullable()->constrained('lughat_word_forms')->nullOnDelete();
                $table->string('surface_form');
                $table->string('normalized_form')->index();
                $table->string('connector', 40)->nullable(); // izafat|null|…
                $table->string('role', 40)->nullable(); // head|complement|modifier|…
                $table->timestamps();

                $table->unique(['expression_id', 'position'], 'lughat_expression_components_pos_unique');
                $table->index(['lemma_id', 'expression_id']);
            });
        }

        if (!Schema::hasTable('lughat_expression_occurrences')) {
            Schema::create('lughat_expression_occurrences', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 64)->nullable()->unique();
                $table->foreignId('expression_id')->constrained('lughat_expressions')->cascadeOnDelete();
                $table->unsignedBigInteger('poetry_id')->nullable()->index();
                $table->unsignedBigInteger('couplet_id')->nullable()->index();
                $table->unsignedInteger('start_token_index');
                $table->unsignedInteger('end_token_index');
                $table->string('surface_text');
                $table->string('normalized_text')->index();
                $table->foreignId('sense_id')->nullable()->constrained('lughat_senses')->nullOnDelete();
                $table->unsignedTinyInteger('confidence')->nullable();
                $table->string('detection_method', 40)->default('rule_based')->index(); // manual|dictionary_match|rule_based|ai
                $table->string('review_status', 30)->default('unreviewed')->index();
                $table->timestamps();

                $table->unique(
                    ['expression_id', 'couplet_id', 'start_token_index', 'end_token_index'],
                    'lughat_expr_occ_span_unique'
                );
                $table->index(['poetry_id', 'expression_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lughat_expression_occurrences');
        Schema::dropIfExists('lughat_expression_components');
        Schema::dropIfExists('lughat_expressions');
    }
};
