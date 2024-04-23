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
        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('slug')->unique(); // optional
            $table->string('title', 255);
            $table->string('bundle_thumbnail')->nullable();
            $table->string('bundle_cover')->nullable();
            $table->string('bundle_layout')->default('couplets');
            $table->text('description')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('bundle_translations', function (Blueprint $table) {
            $table->unsignedBigInteger('bundle_id');
            $table->string('lang_code', 20);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('cascade');
            $table->unique('lang_code'); // Unique composite index
        });

        Schema::create('bundle_types', function(Blueprint $table) {
            $table->bigInteger('reference_id');
            $table->string('reference_type', 255);
            $table->index(['reference_id', 'reference_type']); // Index for performance
        });

        Schema::create('bundle_items', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bundle_id')->nullable();
            $table->bigInteger('reference_id');
            $table->string('reference_type', 255);
            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('cascade');
            $table->index('reference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundles');
        Schema::dropIfExists('bundle_translations');
        Schema::dropIfExists('bundle_types');
        Schema::dropIfExists('bundle_items');
    }
};
