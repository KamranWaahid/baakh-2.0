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
        Schema::create('system_errors', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->string('code')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->longText('trace')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('pending'); // pending, resolved, ignored
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->string('environment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_errors');
    }
};
