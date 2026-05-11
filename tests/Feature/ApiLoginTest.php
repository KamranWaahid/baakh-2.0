<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiLoginTest extends TestCase
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

        $this->createSchema();
    }

    public function test_login_returns_token_for_legacy_plaintext_user(): void
    {
        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'admin@baakh.com',
            'email_hash' => hash('sha256', 'admin@baakh.com'),
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login?lang=sd', [
            'email' => 'admin@baakh.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('user.email', 'admin@baakh.com')
            ->assertJsonPath('user.name', 'Super Admin')
            ->assertJsonStructure(['token']);
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('email_hash')->nullable()->unique();
            $table->string('username')->nullable();
            $table->string('avatar')->nullable();
            $table->string('status')->default('active');
            $table->string('role')->default('user');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
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

        Schema::create('personal_access_tokens', function ($table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}
