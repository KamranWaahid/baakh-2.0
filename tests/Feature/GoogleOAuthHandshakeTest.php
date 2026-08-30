<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\GoogleOAuthHandshake;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleOAuthHandshakeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();
    }

    public function test_handshake_put_and_pull_is_single_use(): void
    {
        $key = GoogleOAuthHandshake::put('1|secret-token', true);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $key);

        $first = GoogleOAuthHandshake::pull($key);
        $this->assertSame('1|secret-token', $first['token']);
        $this->assertTrue($first['new_user']);
        $this->assertNull(GoogleOAuthHandshake::pull($key));
    }

    public function test_handshake_exchange_endpoint_returns_token_once(): void
    {
        $key = GoogleOAuthHandshake::put('plain-token', false);

        $this->postJson('/api/auth/google/handshake', ['k' => $key])
            ->assertOk()
            ->assertJsonPath('token', 'plain-token')
            ->assertJsonPath('new_user', false)
            ->assertJsonPath('token_type', 'Bearer');

        $this->postJson('/api/auth/google/handshake', ['k' => $key])
            ->assertStatus(422)
            ->assertJsonPath('error', 'handshake_expired');
    }

    public function test_handshake_exchange_rejects_invalid_keys(): void
    {
        $this->postJson('/api/auth/google/handshake', ['k' => 'not-hex'])
            ->assertStatus(422);

        $this->postJson('/api/auth/google/handshake', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['k']);
    }

    public function test_google_callback_redirects_with_handshake_not_sanctum_token(): void
    {
        $this->createAuthSchema();

        $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getEmail')->andReturn('google-user@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Google User');
        $googleUser->shouldReceive('getAvatar')->andReturn('');

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google-callback?code=test-oauth-code');
        $response->assertRedirect();

        $location = (string) $response->headers->get('Location');
        $this->assertStringNotContainsString('token=', $location);
        $this->assertStringNotContainsString('|', $location);
        $this->assertMatchesRegularExpression('/auth\/social-callback\?k=[a-f0-9]{64}$/', $location);

        $this->assertTrue(
            User::query()->where('google_id', 'google-123')->exists(),
            'Google user should be created and linked.'
        );
    }

    public function test_android_google_callback_returns_deep_link_bridge(): void
    {
        $response = $this->get('/auth/google-callback');

        $response->assertOk();
        $response->assertSee('baakh://auth/google-callback', false);
        $response->assertSee('Signing in', false);
        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
    }

    public function test_android_client_query_uses_deep_link_even_with_code(): void
    {
        $response = $this->get('/auth/google-callback?code=from-app&client=android');

        $response->assertOk();
        $response->assertSee('baakh://auth/google-callback', false);
    }

    private function createAuthSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->text('name')->nullable();
            $table->text('email')->nullable();
            $table->string('email_hash')->nullable();
            $table->string('username')->nullable();
            $table->string('google_id')->nullable();
            $table->string('status')->default('active');
            $table->string('role')->default('user');
            $table->string('password');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
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
