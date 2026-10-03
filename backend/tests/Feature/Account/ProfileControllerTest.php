<?php

namespace Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_account_can_get_profile_information(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('ACTIVE');

        DB::table('account_profiles')->insert([
            'account_id' => $accountId,
            'display_name' => 'Test User',
            'display_name_changed_at' => now(),
            'profile_photo' => 'profiles/test-user.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/account/profile');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Maklumat profil berjaya diperoleh.',
                'data' => [
                    'account_id' => $accountId,
                    'display_name' => 'Test User',
                    'profile_photo' => 'profiles/test-user.jpg',
                ],
            ]);

        $this->assertNotNull(
            $response->json('data.display_name_changed_at')
        );
    }

    public function test_suspended_account_can_get_profile_information(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('SUSPENDED');

        DB::table('account_profiles')->insert([
            'account_id' => $accountId,
            'display_name' => 'Suspended User',
            'display_name_changed_at' => null,
            'profile_photo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/account/profile');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Maklumat profil berjaya diperoleh.',
                'data' => [
                    'account_id' => $accountId,
                    'display_name' => 'Suspended User',
                    'display_name_changed_at' => null,
                    'profile_photo' => null,
                ],
            ]);
    }

    public function test_account_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/account/profile');

        $response->assertStatus(401);
    }

    private function createAuthenticatedAccount(string $status): array
    {
        $accountId = (string) Str::uuid7();
        $deviceId = (string) Str::uuid7();
        $sessionId = (string) Str::uuid7();
        $now = now();
        $token = Str::random(64);

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
            'device_identifier' => 'profile-test-device',
            'platform' => 'ANDROID',
            'device_name' => 'Profile Test Device',
            'last_seen_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

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
