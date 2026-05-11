<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiSafeUserSerializationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();

        $this->createSchema();
    }

    public function test_api_user_serializes_legacy_plaintext_user_without_decrypting(): void
    {
        $this->insertLegacyUser();

        Sanctum::actingAs(User::find(1));

        $response = $this->getJson('/api/user?lang=sd');

        $response
            ->assertOk()
            ->assertJsonPath('name', 'Legacy Admin')
            ->assertJsonPath('email', 'legacy@example.com');
    }

    public function test_auth_me_serializes_legacy_plaintext_user_without_decrypting(): void
    {
        $this->insertLegacyUser();

        Sanctum::actingAs(User::find(1));

        $response = $this->getJson('/api/auth/me?lang=sd');

        $response
            ->assertOk()
            ->assertJsonPath('user.name', 'Legacy Admin')
            ->assertJsonPath('user.email', 'legacy@example.com');
    }

    public function test_admin_poetry_index_serializes_legacy_user_relation_safely(): void
    {
        $this->withoutMiddleware();
        $this->seedContentWithLegacyUser();

        $response = $this->getJson('/api/admin/poetry?page=1&search=&only_trashed=false&lang=sd');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.user.name', 'Legacy Admin')
            ->assertJsonPath('data.0.info.title', 'Test Poetry');
    }

    public function test_admin_couplets_index_serializes_nested_poetry_user_safely(): void
    {
        $this->withoutMiddleware();
        $this->seedContentWithLegacyUser();

        $response = $this->getJson('/api/admin/couplets?page=1&search=&only_trashed=false&lang=sd');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.poetry.user.name', 'Legacy Admin')
            ->assertJsonPath('data.0.couplet_text', 'First line');
    }

    public function test_admin_dashboard_stats_serializes_legacy_activity_users_safely(): void
    {
        $this->withoutMiddleware();
        $this->seedContentWithLegacyUser();

        DB::table('activity_logs')->insert([
            'user_id' => 1,
            'team_id' => null,
            'action' => 'login',
            'description' => 'User logged in',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'properties' => null,
            'created_at' => now(),
        ]);

        DB::table('feedback')->insert([
            'user_id' => 1,
            'message' => 'Looks good',
            'rating' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reports')->insert([
            'user_id' => 1,
            'poem_id' => 1,
            'poet_id' => null,
            'url' => null,
            'reason' => 'Typo',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/dashboard?lang=sd');

        $response
            ->assertOk()
            ->assertJsonPath('recent_activity.0.user.name', 'Legacy Admin')
            ->assertJsonPath('recent_feedback.0.user.name', 'Legacy Admin')
            ->assertJsonPath('recent_reports.0.reporter', 'Legacy Admin');
    }

    private function seedContentWithLegacyUser(): void
    {
        $this->insertLegacyUser();

        DB::table('poets')->insert([
            'id' => 1,
            'poet_slug' => 'test-poet',
            'poet_pic' => 'default.png',
            'visibility' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('poets_detail')->insert([
            'id' => 1,
            'poet_id' => 1,
            'poet_name' => 'Test Poet',
            'poet_laqab' => 'Test Laqab',
            'poet_bio' => null,
            'lang' => 'sd',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('categories')->insert([
            'id' => 1,
            'slug' => 'ghazal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_details')->insert([
            'id' => 1,
            'cat_id' => 1,
            'cat_name' => 'Ghazal',
            'cat_name_plural' => 'Ghazals',
            'cat_detail' => null,
            'lang' => 'sd',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('poetry_main')->insert([
            'id' => 1,
            'poet_id' => 1,
            'category_id' => 1,
            'topic_category_id' => null,
            'book_id' => null,
            'user_id' => 1,
            'poetry_slug' => 'test-poetry',
            'poetry_title' => 'Test Poetry',
            'poetry_tags' => '[]',
            'content_style' => 'center',
            'visibility' => 1,
            'is_featured' => 0,
            'page_start' => null,
            'page_end' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('poetry_translations')->insert([
            'id' => 1,
            'poetry_id' => 1,
            'title' => 'Test Poetry',
            'info' => null,
            'source' => null,
            'lang' => 'sd',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('poetry_couplets')->insert([
            'id' => 1,
            'poetry_id' => 1,
            'poet_id' => 1,
            'topic_category_id' => null,
            'book_id' => null,
            'couplet_slug' => 'test-poetry-1',
            'couplet_text' => 'First line',
            'couplet_tags' => '[]',
            'lang' => 'sd',
            'page_start' => null,
            'page_end' => null,
            'visibility' => 1,
            'is_featured' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function insertLegacyUser(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Legacy Admin',
            'email' => 'legacy@example.com',
            'email_hash' => hash('sha256', 'legacy@example.com'),
            'username' => 'legacy-admin',
            'phone' => null,
            'whatsapp' => null,
            'avatar' => null,
            'google_id' => null,
            'name_sd' => null,
            'status' => 'active',
            'role' => 'admin',
            'password' => 'not-used',
            'email_verified_at' => now(),
            'last_login_at' => null,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->text('name')->nullable();
            $table->text('email')->nullable();
            $table->string('email_hash')->nullable()->unique();
            $table->string('username')->nullable();
            $table->text('phone')->nullable();
            $table->text('whatsapp')->nullable();
            $table->string('avatar')->nullable();
            $table->string('google_id')->nullable();
            $table->string('name_sd')->nullable();
            $table->string('status')->default('active');
            $table->string('role')->default('user');
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('poets', function ($table) {
            $table->id();
            $table->string('poet_slug');
            $table->string('poet_pic')->nullable();
            $table->boolean('visibility')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('poets_detail', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->string('poet_name');
            $table->string('poet_laqab');
            $table->text('poet_bio')->nullable();
            $table->string('lang')->default('sd');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('slug');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('cat_id')->nullable();
            $table->string('cat_name');
            $table->string('cat_name_plural')->nullable();
            $table->text('cat_detail')->nullable();
            $table->string('lang')->default('sd');
            $table->timestamps();
        });

        Schema::create('topic_categories', function ($table) {
            $table->id();
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('topic_category_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('topic_category_id');
            $table->string('lang');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('poetry_main', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('topic_category_id')->nullable();
            $table->unsignedBigInteger('book_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('poetry_slug');
            $table->string('poetry_title')->nullable();
            $table->text('poetry_tags')->nullable();
            $table->string('content_style')->nullable();
            $table->boolean('visibility')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('page_start')->nullable();
            $table->integer('page_end')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('poetry_translations', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poetry_id');
            $table->string('title');
            $table->longText('info')->nullable();
            $table->text('source')->nullable();
            $table->string('lang')->default('sd');
            $table->timestamps();
        });

        Schema::create('poetry_couplets', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poetry_id')->nullable();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->unsignedBigInteger('topic_category_id')->nullable();
            $table->unsignedBigInteger('book_id')->nullable();
            $table->string('couplet_slug');
            $table->text('couplet_text');
            $table->text('couplet_tags')->nullable();
            $table->string('lang')->nullable();
            $table->integer('page_start')->nullable();
            $table->integer('page_end')->nullable();
            $table->boolean('visibility')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('feedback', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('message');
            $table->integer('rating')->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('poem_id')->nullable();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->string('url')->nullable();
            $table->text('reason');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }
}
