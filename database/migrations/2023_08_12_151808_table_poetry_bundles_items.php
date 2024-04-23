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
        Schema::create('poetry_bundles_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bundle_id')->nullable();
            $table->unsignedBigInteger('couplet_id')->nullable();
            $table->foreign('bundle_id')->references('id')->on('poetry_bundles')->onDelete('cascade');
            $table->foreign('couplet_id')->references('id')->on('poetry_couplets')->onDelete('cascade');
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
