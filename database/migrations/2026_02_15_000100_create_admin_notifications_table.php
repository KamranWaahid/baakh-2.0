<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');           // e.g. 'poetry_created', 'user_registered', 'error_captured'
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable(); // lucide icon name
            $table->string('color')->nullable(); // badge color
            $table->string('link')->nullable();  // admin route to navigate to
            $table->json('data')->nullable();    // extra payload
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
