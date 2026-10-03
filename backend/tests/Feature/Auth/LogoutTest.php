<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $accountId = (string) \Illuminate\Support\Str::uuid7();
        $sessionId = (string) \Illuminate\Support\Str::uuid7();
        $rawToken = bin2hex(random_bytes(32));

        DB::table('accounts')->insert([
            'id' => $accountId,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('app_sessions')->insert([
            'id' => $sessionId,
            'account_id' => $accountId,
            'device_id' => null,
            'app' => 'USER',
            'token_hash' => hash('sha256', $rawToken),
            'status' => 'ACTIVE',
            'created_at' => now(),
            'last_used_at' => null,
            'revoked_at' => null,
            'expires_at' => null,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$rawToken)
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Anda telah log keluar.',
                'data' => null,
            ]);

        $this->assertDatabaseHas('app_sessions', [
            'id' => $sessionId,
            'status' => 'REVOKED',
        ]);

        $this->assertNotNull(
            DB::table('app_sessions')
                ->where('id', $sessionId)
                ->value('revoked_at')
        );
    }

    public function test_logout_requires_bearer_token(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response
            ->assertUnauthorized()
            ->assertJson([
                'status' => 'error',
                'message' => 'Token akses diperlukan.',
            ]);
    }

    public function test_revoked_session_cannot_logout_again(): void
    {
        $accountId = (string) \Illuminate\Support\Str::uuid7();
        $sessionId = (string) \Illuminate\Support\Str::uuid7();
        $rawToken = bin2hex(random_bytes(32));

        DB::table('accounts')->insert([
            'id' => $accountId,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('app_sessions')->insert([
            'id' => $sessionId,
            'account_id' => $accountId,
            'device_id' => null,
            'app' => 'USER',
            'token_hash' => hash('sha256', $rawToken),
            'status' => 'REVOKED',
            'created_at' => now(),
            'last_used_at' => null,
            'revoked_at' => now(),
            'expires_at' => null,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$rawToken)
            ->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }
}
