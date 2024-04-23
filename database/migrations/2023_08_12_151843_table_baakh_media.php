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
        Schema::create('baakh_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('media_type');
            $table->string('media_title');
            $table->string('media_image')->nullable();
            $table->string('media_suorce')->nullable();
            $table->string('bundle_thumbnail')->nullable();
            $table->string('media_url')->nullable();
            $table->string('singer_name')->nullable();
            $table->unsignedBigInteger('poetry_id')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('lang')->default('sd');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('poetry_id')->references('id')->on('poetry_main')->onDelete('cascade');
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
