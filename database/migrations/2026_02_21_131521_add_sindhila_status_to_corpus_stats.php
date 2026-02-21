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
        Schema::table('corpus_stats', function (Blueprint $table) {
            $table->string('sindhila_status')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('corpus_stats', function (Blueprint $table) {
            $table->dropColumn('sindhila_status');
        });
    }
};
