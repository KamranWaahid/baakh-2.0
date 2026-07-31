<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * utf8mb4_unicode_ci treats Arabic vowel marks as equal (نَھن = نُھن).
 * Switch identity columns to utf8mb4_bin so zer/zabar/pesh are distinct.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lughat_lemmas')) {
            DB::statement('ALTER TABLE lughat_lemmas MODIFY lemma VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
            DB::statement('ALTER TABLE lughat_lemmas MODIFY normalized_lemma VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL');
            if (Schema::hasColumn('lughat_lemmas', 'lookup_base')) {
                DB::statement('ALTER TABLE lughat_lemmas MODIFY lookup_base VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL');
            }
        }

        if (Schema::hasTable('lemmas')) {
            DB::statement('ALTER TABLE lemmas MODIFY lemma VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
            if (Schema::hasColumn('lemmas', 'normalized_lemma')) {
                DB::statement('ALTER TABLE lemmas MODIFY normalized_lemma VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL');
            }
            if (Schema::hasColumn('lemmas', 'lookup_base')) {
                DB::statement('ALTER TABLE lemmas MODIFY lookup_base VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL');
            }
        }

        if (Schema::hasTable('lughat_word_forms') && Schema::hasColumn('lughat_word_forms', 'form')) {
            DB::statement('ALTER TABLE lughat_word_forms MODIFY form VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
            if (Schema::hasColumn('lughat_word_forms', 'normalized_form')) {
                DB::statement('ALTER TABLE lughat_word_forms MODIFY normalized_form VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
            }
        }

        if (Schema::hasTable('lughat_inflections') && Schema::hasColumn('lughat_inflections', 'form')) {
            DB::statement('ALTER TABLE lughat_inflections MODIFY form VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
            if (Schema::hasColumn('lughat_inflections', 'normalized_form')) {
                DB::statement('ALTER TABLE lughat_inflections MODIFY normalized_form VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
            }
        }

        if (Schema::hasTable('baakh_roman_words') && Schema::hasColumn('baakh_roman_words', 'word_sd')) {
            DB::statement('ALTER TABLE baakh_roman_words MODIFY word_sd VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
        }
    }

    public function down(): void
    {
        // Leave binary collation in place.
    }
};
