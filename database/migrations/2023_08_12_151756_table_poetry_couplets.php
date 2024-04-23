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
        Schema::create('poetry_couplets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poetry_id')->nullable();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->string('couplet_slug');
            $table->text('couplet_text');
            $table->string('lang')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('poetry_id')->references('id')->on('poetry_main')->onDelete('cascade');
            $table->foreign('poet_id')->references('id')->on('poets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
