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
        Schema::create('prosody_terms', function (Blueprint $table) {
            $table->id();
            $table->string('title_sd');
            $table->string('title_en');
            $table->text('desc_sd')->nullable();
            $table->text('desc_en')->nullable();
            $table->text('tech_detail_sd')->nullable();
            $table->text('tech_detail_en')->nullable();
            $table->string('logic_type')->nullable(); // chhand, arooz, etc.
            $table->string('icon')->default('Info');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prosody_terms');
    }
};
