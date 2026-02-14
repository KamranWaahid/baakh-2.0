<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─── SEO Audits ─────────────────────────────────────
        Schema::create('seo_audits', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500)->index();
            $table->unsignedSmallInteger('status_code')->default(0);
            $table->unsignedInteger('response_time_ms')->default(0);
            $table->boolean('has_h1')->default(false);
            $table->boolean('has_meta_description')->default(false);
            $table->string('meta_title', 500)->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('has_schema')->default(false);
            $table->json('schema_types')->nullable();
            $table->json('broken_links')->nullable();
            $table->json('issues')->nullable();
            $table->decimal('score', 4, 1)->default(0);
            $table->timestamp('crawled_at')->nullable();
            $table->timestamps();

            $table->index('score');
            $table->index('crawled_at');
        });

        // ─── Knowledge Graph Edges ──────────────────────────
        Schema::create('content_graph', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id');
            $table->string('source_type', 50)->index();
            $table->unsignedBigInteger('target_id');
            $table->string('target_type', 50)->index();
            $table->string('relation_type', 50)->index();
            $table->decimal('relation_weight', 5, 3)->default(0.000);
            $table->decimal('semantic_score', 5, 3)->default(0.000);
            $table->timestamps();

            $table->unique(['source_id', 'source_type', 'target_id', 'target_type', 'relation_type'], 'content_graph_unique');
            $table->index(['source_id', 'source_type']);
            $table->index(['target_id', 'target_type']);
        });

        // ─── Computed Page Metadata ─────────────────────────
        Schema::create('mokhii_page_meta', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500)->unique();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_type', 50)->nullable();
            $table->decimal('priority_score', 5, 4)->default(0.5000);
            $table->unsignedInteger('internal_link_count')->default(0);
            $table->decimal('graph_weight', 5, 3)->default(0.000);
            $table->decimal('freshness_score', 5, 3)->default(0.000);
            $table->decimal('engagement_score', 5, 3)->default(0.000);
            $table->text('suggested_meta')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->index(['entity_id', 'entity_type']);
            $table->index('priority_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mokhii_page_meta');
        Schema::dropIfExists('content_graph');
        Schema::dropIfExists('seo_audits');
    }
};
