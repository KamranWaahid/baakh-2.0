<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('senses', function (Blueprint $table) {
            $table->text('definition_en')->nullable()->after('definition');
            $table->text('definition_sd')->nullable()->after('definition_en');
        });
    }

    public function down(): void
    {
        Schema::table('senses', function (Blueprint $table) {
            $table->dropColumn(['definition_en', 'definition_sd']);
        });
    }
};
