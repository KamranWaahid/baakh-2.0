<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baakh Lughat — poetic dictionary (separate from the general Open Lexicon dictionary).
 * Schema mirrors lemmas / senses / morphology / variants / relations / forms.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('lughat_lemmas', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->string('lemma')->index();
            $table->string('normalized_lemma')->nullable()->index();
            $table->string('transliteration')->nullable();
            $table->string('ipa')->nullable();
            $table->string('phonetic')->nullable();
            $table->string('pronunciation_simple')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('syllabification')->nullable();
            $table->string('pos')->nullable()->index();
            $table->text('etymology')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('source_confidence', 5, 2)->nullable();
            $table->json('search_keywords_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->decimal('frequency', 8, 4)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->string('completion_status', 20)->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('completion_notes')->nullable();
            $table->unsignedTinyInteger('completion_score')->default(0);
            $table->json('checklist_json')->nullable();
            $table->boolean('variants_reviewed')->default(false);
            $table->boolean('examples_reviewed')->default(false);
            $table->boolean('morphology_reviewed')->default(false);
            $table->boolean('pronunciation_reviewed')->default(false);
            // Optional poetry provenance (words drawn from verses)
            $table->unsignedBigInteger('poetry_id')->nullable()->index();
            $table->unsignedBigInteger('couplet_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['normalized_lemma']);
        });

        Schema::create('lughat_senses', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->string('lexical_id', 40)->nullable()->unique();
            $table->string('entry_id', 64)->nullable()->index();
            $table->foreignId('lemma_id')->constrained('lughat_lemmas')->onDelete('cascade');
            $table->unsignedInteger('sense_order')->default(0)->index();
            $table->text('definition')->nullable();
            $table->text('definition_en')->nullable();
            $table->json('english_equivalents')->nullable();
            $table->text('definition_sd')->nullable();
            $table->string('short_gloss')->nullable();
            $table->text('full_definition')->nullable();
            $table->text('usage_notes')->nullable();
            $table->string('usage_label')->nullable();
            $table->string('part_of_speech')->nullable()->index();
            $table->string('word_variant')->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('register')->nullable();
            $table->string('dialect')->nullable();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->string('language_direction', 100)->nullable()->index();
            $table->string('source_dictionary', 150)->nullable()->index();
            $table->string('source')->nullable();
            $table->string('source_entry_id', 100)->nullable()->index();
            $table->string('publisher')->nullable();
            $table->string('license')->nullable();
            $table->string('import_version')->nullable();
            $table->text('normalized_definition')->nullable();
            $table->longText('extra')->nullable();
            $table->enum('status', ['pending', 'approved'])->default('pending');
            $table->string('review_status', 30)->default('unreviewed')->index();
            $table->timestamps();
        });

        Schema::create('lughat_sense_examples', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->foreignId('sense_id')->constrained('lughat_senses')->onDelete('cascade');
            $table->text('sentence');
            $table->string('romanization')->nullable();
            $table->text('translation')->nullable();
            $table->string('source')->nullable();
            $table->string('citation')->nullable();
            $table->string('quality_flag', 30)->default('unreviewed')->index();
            $table->string('review_status', 30)->default('unreviewed')->index();
            $table->unsignedBigInteger('poetry_id')->nullable()->index();
            $table->unsignedBigInteger('couplet_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('lughat_morphologies', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->foreignId('lemma_id')->constrained('lughat_lemmas')->onDelete('cascade');
            $table->string('root')->nullable()->index();
            $table->string('pattern')->nullable();
            $table->string('gender')->nullable();
            $table->string('number')->nullable();
            $table->string('case')->nullable();
            $table->string('aspect')->nullable();
            $table->string('tense')->nullable();
            $table->string('review_status', 30)->default('unreviewed')->index();
            $table->timestamps();
        });

        Schema::create('lughat_variants', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->foreignId('lemma_id')->constrained('lughat_lemmas')->onDelete('cascade');
            $table->string('variant')->index();
            $table->string('normalized_variant')->nullable()->index();
            $table->string('type', 50)->default('dialectal');
            $table->string('romanization')->nullable();
            $table->string('dialect')->nullable();
            $table->text('note')->nullable();
            $table->string('source')->nullable();
            $table->string('source_entry_id', 100)->nullable()->index();
            $table->string('review_status', 30)->default('unreviewed')->index();
            $table->timestamps();
        });

        Schema::create('lughat_relations', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->foreignId('lemma_id')->constrained('lughat_lemmas')->onDelete('cascade');
            $table->string('relation_type', 50);
            $table->string('related_word')->nullable();
            $table->string('romanization')->nullable();
            $table->text('note')->nullable();
            $table->string('gloss')->nullable();
            $table->string('part_of_speech')->nullable();
            $table->foreignId('related_lemma_id')->nullable()->constrained('lughat_lemmas')->nullOnDelete();
            $table->string('source')->nullable();
            $table->timestamps();
        });

        Schema::create('lughat_inflections', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->foreignId('lemma_id')->constrained('lughat_lemmas')->onDelete('cascade');
            $table->string('form')->index();
            $table->string('romanization')->nullable();
            $table->string('description')->nullable();
            $table->string('source')->nullable();
            $table->string('review_status', 30)->default('unreviewed')->index();
            $table->timestamps();
        });

        Schema::create('lughat_idiomatic_expressions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->foreignId('lemma_id')->constrained('lughat_lemmas')->onDelete('cascade');
            $table->string('phrase')->index();
            $table->string('romanization')->nullable();
            $table->string('english_gloss')->nullable();
            $table->text('example_sindhi')->nullable();
            $table->text('example_english')->nullable();
            $table->string('source')->nullable();
            $table->string('review_status', 30)->default('unreviewed')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lughat_idiomatic_expressions');
        Schema::dropIfExists('lughat_inflections');
        Schema::dropIfExists('lughat_relations');
        Schema::dropIfExists('lughat_variants');
        Schema::dropIfExists('lughat_morphologies');
        Schema::dropIfExists('lughat_sense_examples');
        Schema::dropIfExists('lughat_senses');
        Schema::dropIfExists('lughat_lemmas');
    }
};
