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
        Schema::create('today_modules', function (Blueprint $table) {
            $table->id();
            $table->date('date_today')->nullable();
            $table->string('table_name')->nullable();
            $table->unsignedBigInteger('table_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('today_modules');
    }
};
