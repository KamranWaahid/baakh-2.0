<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lyrics_parts', function (Blueprint $table) {
            // Logical song section: intro, verse_1, chorus, bridge, outro, instrumental…
            $table->string('section', 40)->nullable()->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('lyrics_parts', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
