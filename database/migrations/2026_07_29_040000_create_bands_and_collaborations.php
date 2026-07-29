<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bands', function (Blueprint $table) {
            $table->id();
            $table->string('band_slug')->unique();
            $table->string('band_pic')->nullable();
            $table->unsignedSmallInteger('formed_year')->nullable();
            $table->boolean('visibility')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bands_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bands')->cascadeOnDelete();
            $table->string('lang', 8);
            $table->string('band_name');
            $table->string('tagline')->nullable();
            $table->text('band_bio')->nullable();
            $table->timestamps();
            $table->unique(['band_id', 'lang']);
        });

        Schema::create('band_singer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bands')->cascadeOnDelete();
            $table->foreignId('singer_id')->constrained('singers')->cascadeOnDelete();
            $table->string('role', 40)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['band_id', 'singer_id']);
        });

        Schema::table('lyrics', function (Blueprint $table) {
            $table->foreignId('band_id')
                ->nullable()
                ->after('singer_id')
                ->constrained('bands')
                ->nullOnDelete();
        });

        Schema::create('lyrics_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lyrics_id')->constrained('lyrics')->cascadeOnDelete();
            $table->string('collaborator_type', 20); // singer | band
            $table->unsignedBigInteger('collaborator_id');
            $table->string('role', 40)->default('feat'); // feat | with | collab
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['collaborator_type', 'collaborator_id'], 'lyrics_collab_entity_idx');
            $table->unique(
                ['lyrics_id', 'collaborator_type', 'collaborator_id'],
                'lyrics_collab_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lyrics_collaborators');

        Schema::table('lyrics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('band_id');
        });

        Schema::dropIfExists('band_singer');
        Schema::dropIfExists('bands_detail');
        Schema::dropIfExists('bands');
    }
};
