<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Baakh Lughat v2 linguistic layers:
 * - word forms (surface → form → lemma)
 * - poetry occurrences (full provenance)
 * - richer inflection grammar features
 * - homograph-safe lemma uniqueness
 * - poetry citation vs editorial examples
 * - romanization approval status
 */
return new class extends Migration {
    public function up(): void
    {
        // -- Lemmas: homographs + frequency caches + romanization gate ---------
        Schema::table('lughat_lemmas', function (Blueprint $table) {
            if (!Schema::hasColumn('lughat_lemmas', 'homograph_number')) {
                $table->unsignedSmallInteger('homograph_number')->default(1)->after('normalized_lemma');
            }
            if (!Schema::hasColumn('lughat_lemmas', 'language')) {
                $table->string('language', 10)->default('sd')->after('homograph_number');
            }
            if (!Schema::hasColumn('lughat_lemmas', 'token_frequency')) {
                $table->unsignedInteger('token_frequency')->default(0)->after('frequency');
            }
            if (!Schema::hasColumn('lughat_lemmas', 'poem_frequency')) {
                $table->unsignedInteger('poem_frequency')->default(0)->after('token_frequency');
            }
            if (!Schema::hasColumn('lughat_lemmas', 'couplet_frequency')) {
                $table->unsignedInteger('couplet_frequency')->default(0)->after('poem_frequency');
            }
            if (!Schema::hasColumn('lughat_lemmas', 'romanization_status')) {
                $table->string('romanization_status', 30)->default('proposed')->after('transliteration');
            }
        });

        // Drop unique(normalized_lemma); replace with (language, normalized_lemma, homograph_number)
        $this->dropUniqueIfExists('lughat_lemmas', 'lughat_lemmas_normalized_lemma_unique');
        if (!$this->indexExists('lughat_lemmas', 'lughat_lemmas_lexeme_unique')) {
            Schema::table('lughat_lemmas', function (Blueprint $table) {
                $table->unique(['language', 'normalized_lemma', 'homograph_number'], 'lughat_lemmas_lexeme_unique');
            });
        }
        // Original migration already indexed normalized_lemma — do not re-add.

        // -- Inflections: form-level grammar ----------------------------------
        Schema::table('lughat_inflections', function (Blueprint $table) {
            if (!Schema::hasColumn('lughat_inflections', 'normalized_form')) {
                $table->string('normalized_form')->nullable()->index()->after('form');
            }
            if (!Schema::hasColumn('lughat_inflections', 'form_type')) {
                $table->string('form_type', 40)->default('inflected')->after('romanization');
            }
            if (!Schema::hasColumn('lughat_inflections', 'gender')) {
                $table->string('gender', 40)->nullable()->after('form_type');
            }
            if (!Schema::hasColumn('lughat_inflections', 'number')) {
                $table->string('number', 40)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('lughat_inflections', 'case_name')) {
                $table->string('case_name', 40)->nullable()->after('number');
            }
            if (!Schema::hasColumn('lughat_inflections', 'person')) {
                $table->string('person', 20)->nullable()->after('case_name');
            }
            if (!Schema::hasColumn('lughat_inflections', 'honorificity')) {
                $table->string('honorificity', 40)->nullable()->after('person');
            }
            if (!Schema::hasColumn('lughat_inflections', 'degree')) {
                $table->string('degree', 40)->nullable()->after('honorificity');
            }
            if (!Schema::hasColumn('lughat_inflections', 'tense')) {
                $table->string('tense', 40)->nullable()->after('degree');
            }
            if (!Schema::hasColumn('lughat_inflections', 'aspect')) {
                $table->string('aspect', 40)->nullable()->after('tense');
            }
            if (!Schema::hasColumn('lughat_inflections', 'mood')) {
                $table->string('mood', 40)->nullable()->after('aspect');
            }
            if (!Schema::hasColumn('lughat_inflections', 'voice')) {
                $table->string('voice', 40)->nullable()->after('mood');
            }
            if (!Schema::hasColumn('lughat_inflections', 'polarity')) {
                $table->string('polarity', 40)->nullable()->after('voice');
            }
            if (!Schema::hasColumn('lughat_inflections', 'stem')) {
                $table->string('stem')->nullable()->after('polarity');
            }
            if (!Schema::hasColumn('lughat_inflections', 'suffix')) {
                $table->string('suffix', 40)->nullable()->after('stem');
            }
            if (!Schema::hasColumn('lughat_inflections', 'analysis_json')) {
                $table->json('analysis_json')->nullable()->after('suffix');
            }
            if (!Schema::hasColumn('lughat_inflections', 'confidence')) {
                $table->unsignedTinyInteger('confidence')->nullable()->after('analysis_json');
            }
            if (!Schema::hasColumn('lughat_inflections', 'word_form_id')) {
                $table->unsignedBigInteger('word_form_id')->nullable()->index()->after('lemma_id');
            }
        });

        // Backfill normalized_form for existing rows
        if (Schema::hasTable('lughat_inflections')) {
            DB::table('lughat_inflections')
                ->whereNull('normalized_form')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('lughat_inflections')->where('id', $row->id)->update([
                            'normalized_form' => mb_strtolower(
                                preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', (string) $row->form) ?? (string) $row->form
                            ),
                        ]);
                    }
                });
        }

        // -- Sense examples: citation type ------------------------------------
        Schema::table('lughat_sense_examples', function (Blueprint $table) {
            if (!Schema::hasColumn('lughat_sense_examples', 'example_type')) {
                $table->string('example_type', 40)->default('editorial')->after('sense_id')->index();
            }
            if (!Schema::hasColumn('lughat_sense_examples', 'generated_by')) {
                $table->string('generated_by', 80)->nullable()->after('example_type');
            }
            if (!Schema::hasColumn('lughat_sense_examples', 'model')) {
                $table->string('model', 120)->nullable()->after('generated_by');
            }
            if (!Schema::hasColumn('lughat_sense_examples', 'prompt_version')) {
                $table->string('prompt_version', 40)->nullable()->after('model');
            }
            if (!Schema::hasColumn('lughat_sense_examples', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('prompt_version');
            }
            if (!Schema::hasColumn('lughat_sense_examples', 'human_reviewed_at')) {
                $table->timestamp('human_reviewed_at')->nullable()->after('generated_at');
            }
        });

        // -- Word forms -------------------------------------------------------
        if (!Schema::hasTable('lughat_word_forms')) {
            Schema::create('lughat_word_forms', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 64)->nullable()->unique();
                $table->foreignId('lemma_id')->nullable()->constrained('lughat_lemmas')->nullOnDelete();
                $table->string('form')->index();
                $table->string('normalized_form')->index();
                $table->string('romanization')->nullable();
                $table->string('form_type', 40)->default('unanalyzed')->index(); // unanalyzed|lemma|inflected|variant|clitic|mwu
                $table->json('morph_features_json')->nullable();
                $table->string('status', 30)->default('pending')->index(); // pending|linked|rejected
                $table->unsignedTinyInteger('confidence')->nullable();
                $table->string('source', 80)->nullable();
                $table->timestamps();

                $table->unique(['normalized_form'], 'lughat_word_forms_normalized_unique');
                $table->index(['lemma_id', 'status']);
            });
        }

        // -- Occurrences (poetry token provenance) ----------------------------
        if (!Schema::hasTable('lughat_occurrences')) {
            Schema::create('lughat_occurrences', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 64)->nullable()->unique();
                $table->foreignId('lemma_id')->nullable()->constrained('lughat_lemmas')->nullOnDelete();
                $table->foreignId('word_form_id')->nullable()->constrained('lughat_word_forms')->nullOnDelete();
                $table->foreignId('inflection_id')->nullable()->constrained('lughat_inflections')->nullOnDelete();
                $table->foreignId('sense_id')->nullable()->constrained('lughat_senses')->nullOnDelete();

                $table->unsignedBigInteger('poetry_id')->nullable()->index();
                $table->unsignedBigInteger('couplet_id')->nullable()->index();
                $table->unsignedBigInteger('poet_id')->nullable()->index();

                $table->string('surface_form');
                $table->string('normalized_form')->index();
                $table->unsignedInteger('token_index');
                $table->unsignedInteger('character_start')->nullable();
                $table->unsignedInteger('character_end')->nullable();

                $table->string('context_before', 255)->nullable();
                $table->string('context_after', 255)->nullable();
                $table->text('full_couplet_snapshot')->nullable();

                $table->string('language', 10)->default('sd')->index();
                $table->boolean('has_diacritics')->default(false);
                $table->string('tokenization_version', 40)->default('sd_ws_v1');
                $table->string('normalization_version', 40)->default('sd_lookup_v1');

                $table->string('analysis_status', 30)->default('unanalyzed')->index(); // unanalyzed|linked|ambiguous|rejected
                $table->unsignedTinyInteger('analysis_confidence')->nullable();

                $table->timestamps();

                $table->unique(
                    ['couplet_id', 'token_index', 'language', 'tokenization_version'],
                    'lughat_occurrences_token_unique'
                );
                $table->index(['poetry_id', 'normalized_form']);
                $table->index(['lemma_id', 'poetry_id']);
            });
        }

        // Optional FKs to poetry (nullable; poetry tables may soft-delete)
        // Kept as unsignedBigInteger indexes only if poetry_main/poetry_couplets FKs are fragile.
    }

    public function down(): void
    {
        Schema::dropIfExists('lughat_occurrences');
        Schema::dropIfExists('lughat_word_forms');

        Schema::table('lughat_sense_examples', function (Blueprint $table) {
            foreach (['example_type', 'generated_by', 'model', 'prompt_version', 'generated_at', 'human_reviewed_at'] as $col) {
                if (Schema::hasColumn('lughat_sense_examples', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('lughat_inflections', function (Blueprint $table) {
            $cols = [
                'normalized_form', 'form_type', 'gender', 'number', 'case_name', 'person',
                'honorificity', 'degree', 'tense', 'aspect', 'mood', 'voice', 'polarity',
                'stem', 'suffix', 'analysis_json', 'confidence', 'word_form_id',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('lughat_inflections', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        $this->dropUniqueIfExists('lughat_lemmas', 'lughat_lemmas_lexeme_unique');
        Schema::table('lughat_lemmas', function (Blueprint $table) {
            foreach ([
                'homograph_number', 'language', 'token_frequency', 'poem_frequency',
                'couplet_frequency', 'romanization_status',
            ] as $col) {
                if (Schema::hasColumn('lughat_lemmas', $col)) {
                    $table->dropColumn($col);
                }
            }
            $table->unique(['normalized_lemma']);
        });
    }

    private function dropUniqueIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropUnique($indexName);
            });
        } catch (\Throwable) {
            // index may already be gone
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $exists = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
                [$table, $indexName]
            );

            return !empty($exists);
        } catch (\Throwable) {
            return false;
        }
    }
};
