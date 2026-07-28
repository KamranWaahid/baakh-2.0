<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Topic Categories Multi-language
        if (!Schema::hasTable('topic_category_details')) {
            Schema::create('topic_category_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('topic_category_id')->constrained('topic_categories')->onDelete('cascade');
                $table->string('lang', 5);
                $table->string('name');
                $table->timestamps();

                $table->unique(['topic_category_id', 'lang']);
            });
        }

        if (Schema::hasColumn('topic_categories', 'name')) {
            $categories = DB::table('topic_categories')->select('id', 'name', 'created_at', 'updated_at')->get();
            foreach ($categories as $category) {
                if ($category->name === null || $category->name === '') {
                    continue;
                }
                DB::table('topic_category_details')->updateOrInsert(
                    ['topic_category_id' => $category->id, 'lang' => 'sd'],
                    [
                        'name' => $category->name,
                        'created_at' => $category->created_at ?? now(),
                        'updated_at' => $category->updated_at ?? now(),
                    ]
                );
            }

            Schema::table('topic_categories', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        // 2. Tags Multi-language
        if (!Schema::hasTable('baakh_tag_details')) {
            Schema::create('baakh_tag_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tag_id')->constrained('baakh_tags')->onDelete('cascade');
                $table->string('lang', 5);
                $table->string('name');
                $table->timestamps();

                $table->unique(['tag_id', 'lang']);
            });
        }

        if (Schema::hasColumn('baakh_tags', 'tag')) {
            $tags = DB::table('baakh_tags')->select('id', 'tag', 'lang', 'created_at', 'updated_at')->get();
            foreach ($tags as $tag) {
                if ($tag->tag === null || $tag->tag === '') {
                    continue;
                }
                DB::table('baakh_tag_details')->updateOrInsert(
                    [
                        'tag_id' => $tag->id,
                        'lang' => $tag->lang ?: 'sd',
                    ],
                    [
                        'name' => $tag->tag,
                        'created_at' => $tag->created_at ?? now(),
                        'updated_at' => $tag->updated_at ?? now(),
                    ]
                );
            }

            Schema::table('baakh_tags', function (Blueprint $table) {
                $table->dropColumn(['tag', 'lang']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baakh_tags', function (Blueprint $table) {
            $table->string('tag')->nullable();
            $table->string('lang', 5)->default('en');
        });

        Schema::dropIfExists('baakh_tag_details');

        Schema::table('topic_categories', function (Blueprint $table) {
            $table->string('name')->nullable();
        });

        Schema::dropIfExists('topic_category_details');
    }
};
