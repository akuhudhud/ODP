<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiSessionAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(AuthenticateApiSession::class)
            ->get('/api/v1/test-authenticated', function () {
                $account = request()->attributes->get('account');
                $session = request()->attributes->get('app_session');

                return response()->json([
                    'status' => 'success',
                    'message' => 'Authenticated.',
                    'data' => [
                        'account_id' => $account->id,
                        'session_id' => $session->id,
                    ],
                ]);
            });
    }

    public function test_request_without_bearer_token_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/test-authenticated');

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
        )->getJson('/api/v1/test-authenticated');

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
        [$accountId, $sessionId, $rawToken] = $this->createSession('ACTIVE');

        $response = $this->withHeader(
            'Authorization',
            'Bearer '.$rawToken
        )->getJson('/api/v1/test-authenticated');

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

        $this->assertDatabaseHas('app_sessions', [
            'id' => $sessionId,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_suspended_account_session_can_authenticate_request(): void
    {
        [$accountId, $sessionId, $rawToken] = $this->createSession('SUSPENDED');

        $response = $this->withHeader(
            'Authorization',
            'Bearer '.$rawToken
        )->getJson('/api/v1/test-authenticated');

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

    public function test_deactivated_account_session_cannot_authenticate_request(): void
    {
        [$accountId, $sessionId, $rawToken] = $this->createSession('DEACTIVATED');

        $response = $this->withHeader(
            'Authorization',
            'Bearer '.$rawToken
        )->getJson('/api/v1/test-authenticated');

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Akaun tidak tersedia.',
                'data' => null,
            ]);

        $this->assertDatabaseHas('app_sessions', [
            'id' => $sessionId,
            'account_id' => $accountId,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_deleted_account_session_cannot_authenticate_request(): void
    {
        [$accountId, $sessionId, $rawToken] = $this->createSession('DELETED');

        $response = $this->withHeader(
            'Authorization',
            'Bearer '.$rawToken
        )->getJson('/api/v1/test-authenticated');

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Akaun tidak tersedia.',
                'data' => null,
            ]);

        $this->assertDatabaseHas('app_sessions', [
            'id' => $sessionId,
            'account_id' => $accountId,
            'status' => 'ACTIVE',
        ]);
    }

    private function createSession(string $accountStatus): array
    {
        $accountId = (string) Str::uuid7();
        $sessionId = (string) Str::uuid7();
        $rawToken = 'test-session-token-'.Str::random(16);
        $now = now();

        DB::table('accounts')->insert([
            'id' => $accountId,
            'status' => $accountStatus,
            'created_at' => $now,
            'updated_at' => $now,
            'deactivated_at' => $accountStatus === 'DEACTIVATED' ? $now : null,
            'deleted_at' => $accountStatus === 'DELETED' ? $now : null,
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

        return [$accountId, $sessionId, $rawToken];
    }
}
