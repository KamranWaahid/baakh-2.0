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
        Schema::create('sindhila_scrapes', function (Blueprint $table) {
            $table->id();
            $table->string('word')->index();
            $table->json('scraped_data')->nullable(); // Array of scraped senses/domains
            $table->string('status')->default('pending')->index(); // e.g. 'pending', 'imported', 'rejected'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sindhila_scrapes');
    }
};
