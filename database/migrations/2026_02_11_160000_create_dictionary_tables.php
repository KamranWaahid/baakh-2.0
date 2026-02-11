<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('lemmas', function (Blueprint $row) {
            $row->id();
            $row->string('lemma')->index();
            $row->string('transliteration')->nullable();
            $row->string('pos')->nullable()->index();
            $row->decimal('frequency', 8, 4)->default(0);
            $row->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $row->timestamps();
        });

        Schema::create('senses', function (Blueprint $row) {
            $row->id();
            $row->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $row->text('definition');
            $row->string('domain')->nullable()->index();
            $row->enum('status', ['pending', 'approved'])->default('pending');
            $row->timestamps();
        });

        Schema::create('sense_examples', function (Blueprint $row) {
            $row->id();
            $row->foreignId('sense_id')->constrained()->onDelete('cascade');
            $row->text('sentence');
            $row->string('source')->nullable();
            $row->foreignId('corpus_sentence_id')->nullable();
            $row->timestamps();
        });

        Schema::create('morphologies', function (Blueprint $row) {
            $row->id();
            $row->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $row->string('root')->nullable()->index();
            $row->string('pattern')->nullable();
            $row->string('gender')->nullable();
            $row->string('number')->nullable();
            $row->string('case')->nullable();
            $row->string('aspect')->nullable();
            $row->string('tense')->nullable();
            $row->timestamps();
        });

        Schema::create('lemma_variants', function (Blueprint $row) {
            $row->id();
            $row->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $row->string('variant')->index();
            $row->enum('type', ['dialectal', 'misspelling', 'historical'])->default('dialectal');
            $row->string('dialect')->nullable();
            $row->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lemma_variants');
        Schema::dropIfExists('morphologies');
        Schema::dropIfExists('sense_examples');
        Schema::dropIfExists('senses');
        Schema::dropIfExists('lemmas');
    }
};
