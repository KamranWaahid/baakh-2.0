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
        Schema::table('users', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
            if (Schema::hasColumn('users', 'phone')) {
                $table->text('phone')->nullable()->change();
            }
            if (Schema::hasColumn('users', 'whatsapp')) {
                $table->text('whatsapp')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            if (Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->change();
            }
            if (Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp')->nullable()->change();
            }
        });
    }
};
