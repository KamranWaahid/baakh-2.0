<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('sindhila_scrapes');

        Schema::table('corpus_stats', function (Blueprint $table) {
            if (Schema::hasColumn('corpus_stats', 'sindhila_status')) {
                $table->dropColumn('sindhila_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('sindhila_scrapes', function (Blueprint $table) {
            $table->id();
            $table->string('word')->index();
            $table->json('scraped_data')->nullable(); // JSON array of senses
            $table->string('status')->default('pending')->index(); // pending, imported, error_parsing
            $table->timestamps();
        });

        Schema::table('corpus_stats', function (Blueprint $table) {
            $table->string('sindhila_status')->nullable()->index()->after('frequency');
        });
    }
};
