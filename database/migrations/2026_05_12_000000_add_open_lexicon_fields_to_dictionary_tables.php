<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('lemmas', 'normalized_lemma')) {
            Schema::table('lemmas', function (Blueprint $table) {
                $table->string('normalized_lemma')->nullable()->after('lemma')->index();
            });
        }

        Schema::table('senses', function (Blueprint $table) {
            if (!Schema::hasColumn('senses', 'lexical_id')) {
                $table->string('lexical_id', 40)->nullable()->after('id')->unique();
            }

            if (!Schema::hasColumn('senses', 'entry_id')) {
                $table->string('entry_id', 64)->nullable()->after('lexical_id')->index();
            }

            if (!Schema::hasColumn('senses', 'part_of_speech')) {
                $table->string('part_of_speech')->nullable()->after('definition_sd')->index();
            }

            if (!Schema::hasColumn('senses', 'word_variant')) {
                $table->string('word_variant')->nullable()->after('part_of_speech');
            }

            if (!Schema::hasColumn('senses', 'language_direction')) {
                $table->string('language_direction', 100)->nullable()->after('domain')->index();
            }

            if (!Schema::hasColumn('senses', 'source_dictionary')) {
                $table->string('source_dictionary', 150)->nullable()->after('language_direction')->index();
            }

            if (!Schema::hasColumn('senses', 'normalized_definition')) {
                $table->text('normalized_definition')->nullable()->after('source_dictionary');
            }

            if (!Schema::hasColumn('senses', 'extra')) {
                $table->longText('extra')->nullable()->after('normalized_definition');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('senses', 'lexical_id')) {
            Schema::table('senses', function (Blueprint $table) {
                $table->dropUnique('senses_lexical_id_unique');
            });
        }

        foreach ([
            ['senses', 'entry_id', 'senses_entry_id_index'],
            ['senses', 'part_of_speech', 'senses_part_of_speech_index'],
            ['senses', 'language_direction', 'senses_language_direction_index'],
            ['senses', 'source_dictionary', 'senses_source_dictionary_index'],
            ['lemmas', 'normalized_lemma', 'lemmas_normalized_lemma_index'],
        ] as [$tableName, $columnName, $indexName]) {
            if (Schema::hasColumn($tableName, $columnName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        }

        $senseColumns = [
            'lexical_id',
            'entry_id',
            'part_of_speech',
            'word_variant',
            'language_direction',
            'source_dictionary',
            'normalized_definition',
            'extra',
        ];

        Schema::table('senses', function (Blueprint $table) use ($senseColumns) {
            foreach ($senseColumns as $column) {
                if (Schema::hasColumn('senses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('lemmas', 'normalized_lemma')) {
            Schema::table('lemmas', function (Blueprint $table) {
                $table->dropColumn('normalized_lemma');
            });
        }
    }
};
