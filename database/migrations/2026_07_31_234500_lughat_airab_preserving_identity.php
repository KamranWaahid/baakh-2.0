<?php

use App\Support\DictionaryText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Preserve zer/zabar/pesh on lemma identity.
 * - normalized_lemma = airab-preserving identity key
 * - lookup_base = diacritic-stripped fuzzy key (search / ambiguous fallback)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lughat_lemmas')) {
            return;
        }

        if (!Schema::hasColumn('lughat_lemmas', 'lookup_base')) {
            Schema::table('lughat_lemmas', function (Blueprint $table) {
                $table->string('lookup_base', 255)->nullable()->after('normalized_lemma');
                $table->index('lookup_base', 'lughat_lemmas_lookup_base_index');
            });
        }

        // Recompute keys from stored lemma text (keeps airab on identity).
        DB::table('lughat_lemmas')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $lemma = (string) ($row->lemma ?? '');
                $identity = DictionaryText::normalizeForIdentity($lemma);
                $base = DictionaryText::lookupBase($lemma);

                DB::table('lughat_lemmas')->where('id', $row->id)->update([
                    'normalized_lemma' => $identity !== '' ? $identity : $row->normalized_lemma,
                    'lookup_base' => $base !== '' ? $base : null,
                ]);
            }
        });

        // Word forms / inflections: keep airab on normalized_form when possible.
        if (Schema::hasTable('lughat_word_forms') && Schema::hasColumn('lughat_word_forms', 'normalized_form')) {
            DB::table('lughat_word_forms')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $form = (string) ($row->form ?? '');
                    $identity = DictionaryText::normalizeForIdentity($form);
                    if ($identity === '') {
                        continue;
                    }
                    DB::table('lughat_word_forms')->where('id', $row->id)->update([
                        'normalized_form' => $identity,
                    ]);
                }
            });
        }

        if (Schema::hasTable('lughat_inflections') && Schema::hasColumn('lughat_inflections', 'normalized_form')) {
            DB::table('lughat_inflections')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $form = (string) ($row->form ?? '');
                    $identity = DictionaryText::normalizeForIdentity($form);
                    if ($identity === '') {
                        continue;
                    }
                    DB::table('lughat_inflections')->where('id', $row->id)->update([
                        'normalized_form' => $identity,
                    ]);
                }
            });
        }

        // General dictionary lemmas: preserve airab in normalized_lemma too.
        if (Schema::hasTable('lemmas') && Schema::hasColumn('lemmas', 'normalized_lemma')) {
            if (!Schema::hasColumn('lemmas', 'lookup_base')) {
                Schema::table('lemmas', function (Blueprint $table) {
                    $table->string('lookup_base', 255)->nullable()->after('normalized_lemma');
                    $table->index('lookup_base', 'lemmas_lookup_base_index');
                });
            }

            DB::table('lemmas')->orderBy('id')->chunkById(1000, function ($rows) {
                foreach ($rows as $row) {
                    $lemma = (string) ($row->lemma ?? '');
                    $identity = DictionaryText::normalizeForIdentity($lemma);
                    $base = DictionaryText::lookupBase($lemma);

                    DB::table('lemmas')->where('id', $row->id)->update([
                        'normalized_lemma' => $identity !== '' ? $identity : $row->normalized_lemma,
                        'lookup_base' => $base !== '' ? $base : null,
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lughat_lemmas') && Schema::hasColumn('lughat_lemmas', 'lookup_base')) {
            Schema::table('lughat_lemmas', function (Blueprint $table) {
                $table->dropIndex('lughat_lemmas_lookup_base_index');
                $table->dropColumn('lookup_base');
            });
        }

        if (Schema::hasTable('lemmas') && Schema::hasColumn('lemmas', 'lookup_base')) {
            Schema::table('lemmas', function (Blueprint $table) {
                $table->dropIndex('lemmas_lookup_base_index');
                $table->dropColumn('lookup_base');
            });
        }
    }
};
