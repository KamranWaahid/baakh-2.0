<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('poetry_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poetry_id');
            $table->string('title', 255);
            $table->longText('info')->nullable();
            $table->text('source')->nullable();
            $table->string('lang', 20)->default('sd');
            $table->timestamps();

            $table->foreign('poetry_id')->references('id')->on('poetry_main')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poetry_translations');
    }
};
