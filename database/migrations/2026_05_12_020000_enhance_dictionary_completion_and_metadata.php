<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lemmas', function (Blueprint $table) {
            if (!Schema::hasColumn('lemmas', 'public_id')) {
                $table->string('public_id', 64)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('lemmas', 'completion_status')) {
                $table->string('completion_status', 20)->default('pending')->index()->after('status');
            }
            if (!Schema::hasColumn('lemmas', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('completion_status');
            }
            if (!Schema::hasColumn('lemmas', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('lemmas', 'completion_notes')) {
                $table->text('completion_notes')->nullable()->after('completed_by');
            }
            if (!Schema::hasColumn('lemmas', 'completion_score')) {
                $table->unsignedTinyInteger('completion_score')->default(0)->after('completion_notes');
            }
            if (!Schema::hasColumn('lemmas', 'checklist_json')) {
                $table->json('checklist_json')->nullable()->after('completion_score');
            }
            if (!Schema::hasColumn('lemmas', 'variants_reviewed')) {
                $table->boolean('variants_reviewed')->default(false)->after('checklist_json');
            }
            if (!Schema::hasColumn('lemmas', 'examples_reviewed')) {
                $table->boolean('examples_reviewed')->default(false)->after('variants_reviewed');
            }
            if (!Schema::hasColumn('lemmas', 'morphology_reviewed')) {
                $table->boolean('morphology_reviewed')->default(false)->after('examples_reviewed');
            }
            if (!Schema::hasColumn('lemmas', 'pronunciation_reviewed')) {
                $table->boolean('pronunciation_reviewed')->default(false)->after('morphology_reviewed');
            }
            if (!Schema::hasColumn('lemmas', 'ipa')) {
                $table->string('ipa')->nullable()->after('transliteration');
            }
            if (!Schema::hasColumn('lemmas', 'phonetic')) {
                $table->string('phonetic')->nullable()->after('ipa');
            }
            if (!Schema::hasColumn('lemmas', 'audio_url')) {
                $table->string('audio_url')->nullable()->after('phonetic');
            }
            if (!Schema::hasColumn('lemmas', 'syllabification')) {
                $table->string('syllabification')->nullable()->after('audio_url');
            }
        });

        Schema::table('senses', function (Blueprint $table) {
            if (!Schema::hasColumn('senses', 'public_id')) {
                $table->string('public_id', 64)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('senses', 'sense_order')) {
                $table->unsignedInteger('sense_order')->default(0)->index()->after('lemma_id');
            }
            if (!Schema::hasColumn('senses', 'short_gloss')) {
                $table->string('short_gloss')->nullable()->after('definition');
            }
            if (!Schema::hasColumn('senses', 'full_definition')) {
                $table->text('full_definition')->nullable()->after('short_gloss');
            }
            if (!Schema::hasColumn('senses', 'usage_notes')) {
                $table->text('usage_notes')->nullable()->after('full_definition');
            }
            if (!Schema::hasColumn('senses', 'register')) {
                $table->string('register')->nullable()->after('usage_notes');
            }
            if (!Schema::hasColumn('senses', 'dialect')) {
                $table->string('dialect')->nullable()->after('register');
            }
            if (!Schema::hasColumn('senses', 'confidence')) {
                $table->unsignedTinyInteger('confidence')->nullable()->after('dialect');
            }
            if (!Schema::hasColumn('senses', 'review_status')) {
                $table->string('review_status', 30)->default('unreviewed')->index()->after('status');
            }
            if (!Schema::hasColumn('senses', 'source')) {
                $table->string('source')->nullable()->after('source_dictionary');
            }
            if (!Schema::hasColumn('senses', 'source_entry_id')) {
                $table->string('source_entry_id', 100)->nullable()->index()->after('source');
            }
            if (!Schema::hasColumn('senses', 'publisher')) {
                $table->string('publisher')->nullable()->after('source_entry_id');
            }
            if (!Schema::hasColumn('senses', 'license')) {
                $table->string('license')->nullable()->after('publisher');
            }
            if (!Schema::hasColumn('senses', 'import_version')) {
                $table->string('import_version')->nullable()->after('license');
            }
        });

        Schema::table('sense_examples', function (Blueprint $table) {
            if (!Schema::hasColumn('sense_examples', 'public_id')) {
                $table->string('public_id', 64)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('sense_examples', 'translation')) {
                $table->text('translation')->nullable()->after('sentence');
            }
            if (!Schema::hasColumn('sense_examples', 'citation')) {
                $table->string('citation')->nullable()->after('source');
            }
            if (!Schema::hasColumn('sense_examples', 'quality_flag')) {
                $table->string('quality_flag', 30)->default('unreviewed')->index()->after('citation');
            }
            if (!Schema::hasColumn('sense_examples', 'review_status')) {
                $table->string('review_status', 30)->default('unreviewed')->index()->after('quality_flag');
            }
        });

        Schema::table('morphologies', function (Blueprint $table) {
            if (!Schema::hasColumn('morphologies', 'public_id')) {
                $table->string('public_id', 64)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('morphologies', 'review_status')) {
                $table->string('review_status', 30)->default('unreviewed')->index()->after('tense');
            }
        });

        Schema::table('lemma_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('lemma_variants', 'public_id')) {
                $table->string('public_id', 64)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('lemma_variants', 'source')) {
                $table->string('source')->nullable()->after('dialect');
            }
            if (!Schema::hasColumn('lemma_variants', 'source_entry_id')) {
                $table->string('source_entry_id', 100)->nullable()->index()->after('source');
            }
            if (!Schema::hasColumn('lemma_variants', 'review_status')) {
                $table->string('review_status', 30)->default('unreviewed')->index()->after('source_entry_id');
            }
        });

        Schema::table('lemma_relations', function (Blueprint $table) {
            if (!Schema::hasColumn('lemma_relations', 'public_id')) {
                $table->string('public_id', 64)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('lemma_relations', 'source')) {
                $table->string('source')->nullable()->after('related_lemma_id');
            }
        });

        $this->backfillPublicIds('lemmas', 'lem');
        $this->backfillPublicIds('senses', 'sen');
        $this->backfillPublicIds('sense_examples', 'ex');
        $this->backfillPublicIds('morphologies', 'morph');
        $this->backfillPublicIds('lemma_variants', 'var');
        $this->backfillPublicIds('lemma_relations', 'rel');
    }

    public function down(): void
    {
        if (Schema::hasColumn('lemmas', 'completed_by')) {
            Schema::table('lemmas', function (Blueprint $table) {
                $table->dropForeign(['completed_by']);
            });
        }

        $this->dropColumns('lemma_relations', ['source', 'public_id']);
        $this->dropColumns('lemma_variants', ['review_status', 'source_entry_id', 'source', 'public_id']);
        $this->dropColumns('morphologies', ['review_status', 'public_id']);
        $this->dropColumns('sense_examples', ['review_status', 'quality_flag', 'citation', 'translation', 'public_id']);
        $this->dropColumns('senses', [
            'import_version',
            'license',
            'publisher',
            'source_entry_id',
            'source',
            'review_status',
            'confidence',
            'dialect',
            'register',
            'usage_notes',
            'full_definition',
            'short_gloss',
            'sense_order',
            'public_id',
        ]);
        $this->dropColumns('lemmas', [
            'syllabification',
            'audio_url',
            'phonetic',
            'ipa',
            'pronunciation_reviewed',
            'morphology_reviewed',
            'examples_reviewed',
            'variants_reviewed',
            'checklist_json',
            'completion_score',
            'completion_notes',
            'completed_by',
            'completed_at',
            'completion_status',
            'public_id',
        ]);
    }

    private function backfillPublicIds(string $table, string $prefix): void
    {
        if (!Schema::hasColumn($table, 'public_id')) {
            return;
        }

        DB::table($table)
            ->whereNull('public_id')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(1000, function ($rows) use ($table, $prefix) {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['public_id' => $prefix . '_' . $row->id]);
                }
            });
    }

    private function dropColumns(string $table, array $columns): void
    {
        $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
        if ($existing === []) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
