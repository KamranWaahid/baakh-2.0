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
        Schema::create('poets_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->string('poet_name');
            $table->string('poet_laqab');
            $table->text('poet_bio')->nullable();
            $table->unsignedBigInteger('birth_place')->nullable();
            $table->unsignedBigInteger('death_place')->nullable();
            $table->string('lang')->default('sd');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('poet_id')->references('id')->on('poets')->onDelete('cascade');
            $table->foreign('birth_place')->references('id')->on('location_cities')->onDelete('cascade');
            $table->foreign('death_place')->references('id')->on('location_cities')->onDelete('cascade');
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
