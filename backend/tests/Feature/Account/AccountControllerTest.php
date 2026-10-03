<?php

namespace Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_account_can_get_account_information(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('ACTIVE');

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/account');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Maklumat akaun berjaya diperoleh.',
                'data' => [
                    'account_id' => $accountId,
                    'status' => 'ACTIVE',
                ],
            ]);
    }

    public function test_suspended_account_can_get_account_information(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('SUSPENDED');

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/account');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Maklumat akaun berjaya diperoleh.',
                'data' => [
                    'account_id' => $accountId,
                    'status' => 'SUSPENDED',
                ],
            ]);
    }

    public function test_account_information_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/account');

        $response->assertStatus(401);
    }

    private function createAuthenticatedAccount(string $status): array
    {
        $accountId = (string) Str::uuid7();
        $deviceId = (string) Str::uuid7();
        $sessionId = (string) Str::uuid7();
        $now = now();

        DB::table('accounts')->insert([
            'id' => $accountId,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
            'deactivated_at' => null,
            'deleted_at' => null,
        ]);

        DB::table('account_devices')->insert([
            'id' => $deviceId,
            'account_id' => $accountId,
            'device_identifier' => 'account-test-device',
            'platform' => 'ANDROID',
            'device_name' => 'Account Test Device',
            'last_seen_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = Str::random(64);

        DB::table('app_sessions')->insert([
            'id' => $sessionId,
            'account_id' => $accountId,
            'device_id' => $deviceId,
            'app' => 'USER',
            'token_hash' => hash('sha256', $token),
            'status' => 'ACTIVE',
            'last_used_at' => $now,
            'expires_at' => $now->copy()->addDays(30),
            'revoked_at' => null,
            'created_at' => $now,
        ]);

        return [$accountId, $token];
    }
}
