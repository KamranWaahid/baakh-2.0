<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lyrics', function (Blueprint $table) {
            if (!Schema::hasColumn('lyrics', 'poetry_id')) {
                $table->unsignedBigInteger('poetry_id')->nullable()->after('genre_id');
                $table->foreign('poetry_id')
                    ->references('id')
                    ->on('poetry_main')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lyrics', function (Blueprint $table) {
            if (Schema::hasColumn('lyrics', 'poetry_id')) {
                $table->dropForeign(['poetry_id']);
                $table->dropColumn('poetry_id');
            }
        });
    }
};
