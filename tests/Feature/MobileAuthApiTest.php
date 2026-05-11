<?php

namespace Tests\Feature;

use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class MobileAuthApiTest extends TestCase
{
    public function test_auth_ui_route_returns_mobile_config(): void
    {
        $response = $this->getJson('/api/v1/auth/ui');

        $response->assertOk()
            ->assertJsonPath('auth.type', 'bearer_token')
            ->assertJsonPath('auth.me_endpoint', url('/api/auth/me'))
            ->assertJsonPath('google.mobile_endpoint', url('/api/auth/google/mobile'));
    }

    public function test_mobile_google_requires_a_token(): void
    {
        $response = $this->postJson('/api/auth/google/mobile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_token']);
    }

    public function test_google_authorize_routes_start_google_oauth_redirect(): void
    {
        $googleRedirect = 'https://accounts.google.com/o/oauth2/v2/auth?client_id=test';
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->twice()->andReturnSelf();
        $provider->shouldReceive('redirect')->twice()->andReturnUsing(fn () => redirect()->away($googleRedirect));

        Socialite::shouldReceive('driver')
            ->twice()
            ->with('google')
            ->andReturn($provider);

        $this->get('/api/auth/google/redirect')
            ->assertStatus(302)
            ->assertHeader('Location', $googleRedirect);

        $this->get('/api/v1/auth/google/redirect')
            ->assertStatus(302)
            ->assertHeader('Location', $googleRedirect);
    }

    public function test_auth_me_stays_unauthenticated_without_login(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_cors_allows_expo_localhost_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:8081',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/v1/feed');

        $response->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8081');
    }
}
