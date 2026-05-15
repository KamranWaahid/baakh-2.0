<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lemmas', function (Blueprint $table) {
            if (!Schema::hasColumn('lemmas', 'pronunciation_simple')) {
                $table->string('pronunciation_simple')->nullable()->after('phonetic');
            }
            if (!Schema::hasColumn('lemmas', 'etymology')) {
                $table->text('etymology')->nullable()->after('pos');
            }
            if (!Schema::hasColumn('lemmas', 'notes')) {
                $table->text('notes')->nullable()->after('etymology');
            }
            if (!Schema::hasColumn('lemmas', 'source_confidence')) {
                $table->decimal('source_confidence', 5, 2)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('lemmas', 'search_keywords_json')) {
                $table->json('search_keywords_json')->nullable()->after('source_confidence');
            }
            if (!Schema::hasColumn('lemmas', 'metadata_json')) {
                $table->json('metadata_json')->nullable()->after('search_keywords_json');
            }
        });

        Schema::table('senses', function (Blueprint $table) {
            if (!Schema::hasColumn('senses', 'english_equivalents')) {
                $table->json('english_equivalents')->nullable()->after('definition_en');
            }
            if (!Schema::hasColumn('senses', 'usage_label')) {
                $table->string('usage_label')->nullable()->after('usage_notes');
            }
        });

        Schema::table('sense_examples', function (Blueprint $table) {
            if (!Schema::hasColumn('sense_examples', 'romanization')) {
                $table->string('romanization')->nullable()->after('sentence');
            }
        });

        Schema::table('lemma_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('lemma_variants', 'normalized_variant')) {
                $table->string('normalized_variant')->nullable()->after('variant')->index();
            }
            if (!Schema::hasColumn('lemma_variants', 'romanization')) {
                $table->string('romanization')->nullable()->after('type');
            }
            if (!Schema::hasColumn('lemma_variants', 'note')) {
                $table->text('note')->nullable()->after('dialect');
            }
        });

        Schema::table('lemma_relations', function (Blueprint $table) {
            if (!Schema::hasColumn('lemma_relations', 'romanization')) {
                $table->string('romanization')->nullable()->after('related_word');
            }
            if (!Schema::hasColumn('lemma_relations', 'note')) {
                $table->text('note')->nullable()->after('romanization');
            }
            if (!Schema::hasColumn('lemma_relations', 'gloss')) {
                $table->string('gloss')->nullable()->after('note');
            }
            if (!Schema::hasColumn('lemma_relations', 'part_of_speech')) {
                $table->string('part_of_speech')->nullable()->after('gloss');
            }
        });

        $this->widenRelationType();

        if (!Schema::hasTable('lemma_inflections')) {
            Schema::create('lemma_inflections', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 64)->nullable()->unique();
                $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
                $table->string('form')->index();
                $table->string('romanization')->nullable();
                $table->string('description')->nullable();
                $table->string('source')->nullable();
                $table->string('review_status', 30)->default('unreviewed')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lemma_idiomatic_expressions')) {
            Schema::create('lemma_idiomatic_expressions', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 64)->nullable()->unique();
                $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('lemma_idiomatic_expressions');
        Schema::dropIfExists('lemma_inflections');

        $this->dropColumns('lemma_relations', ['part_of_speech', 'gloss', 'note', 'romanization']);
        $this->dropColumns('lemma_variants', ['note', 'romanization', 'normalized_variant']);
        $this->dropColumns('sense_examples', ['romanization']);
        $this->dropColumns('senses', ['usage_label', 'english_equivalents']);
        $this->dropColumns('lemmas', [
            'metadata_json',
            'search_keywords_json',
            'source_confidence',
            'notes',
            'etymology',
            'pronunciation_simple',
        ]);
    }

    private function widenRelationType(): void
    {
        if (!Schema::hasTable('lemma_relations') || !Schema::hasColumn('lemma_relations', 'relation_type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE lemma_relations MODIFY relation_type VARCHAR(50) NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE lemma_relations ALTER COLUMN relation_type TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE lemma_relations ALTER COLUMN relation_type SET NOT NULL");
        }
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
