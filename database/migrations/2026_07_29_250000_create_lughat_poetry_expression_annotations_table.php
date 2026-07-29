<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Poet-intended multiword expressions in a poetry line (e.g. جامِ محبت).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lughat_poetry_expression_annotations')) {
            return;
        }

        Schema::create('lughat_poetry_expression_annotations', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->nullable()->unique();
            $table->unsignedBigInteger('poetry_id')->index();
            $table->unsignedBigInteger('couplet_id')->nullable()->index();
            $table->unsignedInteger('couplet_index')->default(0);
            $table->unsignedInteger('start_token_index')->default(0);
            $table->unsignedInteger('end_token_index')->default(0);
            $table->string('surface_text');
            $table->string('normalized_text')->index();
            $table->foreignId('expression_id')->constrained('lughat_expressions')->cascadeOnDelete();
            $table->string('expression_type', 40)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(
                ['poetry_id', 'couplet_index', 'start_token_index', 'end_token_index'],
                'lughat_poetry_expr_ann_span_unique'
            );
            $table->index(['expression_id', 'poetry_id'], 'lughat_poetry_expr_ann_expr_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lughat_poetry_expression_annotations');
    }
};
