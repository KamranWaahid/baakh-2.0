<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('singers', function (Blueprint $table) {
            $table->id();
            $table->string('singer_slug')->unique();
            $table->string('singer_pic')->nullable();
            $table->boolean('visibility')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('singers_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('singer_id');
            $table->string('lang', 10)->default('sd');
            $table->string('singer_name');
            $table->text('singer_bio')->nullable();
            $table->timestamps();

            $table->foreign('singer_id')->references('id')->on('singers')->onDelete('cascade');
            $table->unique(['singer_id', 'lang']);
        });

        Schema::create('lyrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('singer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('lyrics_slug')->unique();
            $table->json('lyrics_tags')->nullable();
            $table->boolean('visibility')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('content_style')->default('center');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('singer_id')->references('id')->on('singers')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('visibility');
            $table->index('is_featured');
        });

        Schema::create('lyrics_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lyrics_id');
            $table->string('lang', 10)->default('sd');
            $table->string('title');
            $table->text('info')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->foreign('lyrics_id')->references('id')->on('lyrics')->onDelete('cascade');
            $table->unique(['lyrics_id', 'lang']);
        });

        Schema::create('lyrics_parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lyrics_id');
            $table->unsignedInteger('sort_order')->default(0);
            // sung | couplet | spoken | explanation | other
            $table->string('kind', 32)->default('sung');
            // intro | mid | body | outro | other (optional song position)
            $table->string('role', 32)->nullable();
            // exact | adapted | inspired | original | unknown
            $table->string('relation', 32)->default('original');
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->unsignedBigInteger('poetry_id')->nullable();
            $table->unsignedBigInteger('couplet_id')->nullable();
            $table->text('text_sd')->nullable();
            $table->text('text_roman')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lyrics_id')->references('id')->on('lyrics')->onDelete('cascade');
            $table->foreign('poet_id')->references('id')->on('poets')->onDelete('set null');
            $table->foreign('poetry_id')->references('id')->on('poetry_main')->onDelete('set null');
            $table->foreign('couplet_id')->references('id')->on('poetry_couplets')->onDelete('set null');
            $table->index(['lyrics_id', 'sort_order']);
            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lyrics_parts');
        Schema::dropIfExists('lyrics_translations');
        Schema::dropIfExists('lyrics');
        Schema::dropIfExists('singers_detail');
        Schema::dropIfExists('singers');
    }
};
