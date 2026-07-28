<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lyrics', function (Blueprint $table) {
            $table->string('music_url')->nullable()->after('content_style');
            $table->string('music_title')->nullable()->after('music_url');
            // youtube | audio | other
            $table->string('music_type', 32)->nullable()->after('music_title');
        });
    }

    public function down(): void
    {
        Schema::table('lyrics', function (Blueprint $table) {
            $table->dropColumn(['music_url', 'music_title', 'music_type']);
        });
    }
};
