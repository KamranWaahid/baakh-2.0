<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\User;

class PoetryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_success_for_valid_poetry()
    {
        $user = User::factory()->create();
        $poet = Poets::create(['poet_slug' => 'test-poet', 'visibility' => 1]);

        $poetry = Poetry::create([
            'poet_id' => $poet->id,
            'poetry_slug' => 'test-poetry',
            'visibility' => 1,
            'user_id' => $user->id
        ]);

        $response = $this->get('/api/v1/poetry/test-poetry');

        $response->assertStatus(200);
    }

    public function test_api_fails_gracefully_for_orphaned_poetry()
    {
        $user = User::factory()->create();
        $response = $this->post('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Create poetry with non-existent poet_id
        $poetry = Poetry::create([
            'poet_id' => 99999,
            'poetry_slug' => 'orphaned-poetry',
            'visibility' => 1,
            'user_id' => $user->id
        ]);

        $response = $this->get('/api/v1/poetry/orphaned-poetry');

        // It may currently return 500, we want to fix this.
        // For reproduction, we check if it is 500.
        // If it's already fixed (unexpectedly), it would be 200 or 404.

        $response->assertStatus(200);
    }
}
