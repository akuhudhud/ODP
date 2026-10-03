<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiSessionAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_without_bearer_token_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/authenticated-test');

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Sesi tidak sah.',
                'data' => null,
            ]);
    }

    public function test_request_with_invalid_bearer_token_returns_unauthorized(): void
    {
        $response = $this->withHeader(
            'Authorization',
            'Bearer invalid-token'
        )->getJson('/api/v1/authenticated-test');

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Sesi tidak sah atau telah tamat.',
                'data' => null,
            ]);
    }

    public function test_active_session_can_authenticate_request(): void
    {
        $accountId = (string) Str::uuid7();
        $sessionId = (string) Str::uuid7();
        $rawToken = 'test-session-token';
        $now = now();

        DB::table('accounts')->insert([
            'id' => $accountId,
            'status' => 'ACTIVE',
            'created_at' => $now,
            'updated_at' => $now,
            'deactivated_at' => null,
            'deleted_at' => null,
        ]);

        DB::table('app_sessions')->insert([
            'id' => $sessionId,
            'account_id' => $accountId,
            'device_id' => null,
            'app' => 'USER',
            'token_hash' => hash('sha256', $rawToken),
            'status' => 'ACTIVE',
            'created_at' => $now,
            'last_used_at' => null,
            'revoked_at' => null,
            'expires_at' => null,
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer '.$rawToken
        )->getJson('/api/v1/authenticated-test');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Authenticated.',
                'data' => [
                    'account_id' => $accountId,
                    'session_id' => $sessionId,
                ],
            ]);
    }
}
