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
        // 1. Drop unique index
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        // 2. Change column type
        Schema::table('users', function (Blueprint $table) {
            $table->text('email')->change();
        });

        // 3. Add new hash column
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_hash')->nullable()->unique()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->change();
            $table->dropColumn('email_hash');
        });
    }
};
