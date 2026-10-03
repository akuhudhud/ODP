<?php

namespace Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileUpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_account_can_update_profile(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('ACTIVE');

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->putJson('/api/v1/account/profile', [
            'display_name' => 'Nama Baharu',
            'profile_photo' => 'profiles/nama-baharu.jpg',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Profil berjaya dikemas kini.',
                'data' => [
                    'account_id' => $accountId,
                ],
            ]);

        $this->assertDatabaseHas('account_profiles', [
            'account_id' => $accountId,
            'display_name' => 'Nama Baharu',
            'profile_photo' => 'profiles/nama-baharu.jpg',
        ]);
    }

    public function test_suspended_account_cannot_update_profile(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('SUSPENDED');

        DB::table('account_profiles')->insert([
            'account_id' => $accountId,
            'display_name' => 'Nama Lama',
            'display_name_changed_at' => null,
            'profile_photo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->putJson('/api/v1/account/profile', [
            'display_name' => 'Nama Baharu',
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Profil tidak boleh diubah ketika akaun tidak aktif.',
            ]);

        $this->assertDatabaseHas('account_profiles', [
            'account_id' => $accountId,
            'display_name' => 'Nama Lama',
        ]);
    }

    public function test_display_name_rejects_numbers_and_special_characters(): void
    {
        [, $token] = $this->createAuthenticatedAccount('ACTIVE');

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->putJson('/api/v1/account/profile', [
            'display_name' => 'Nama123!',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Maklumat profil tidak sah.',
            ]);

        $this->assertArrayHasKey(
            'display_name',
            $response->json('data')
        );
    }

    public function test_display_name_cannot_be_changed_within_30_days(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('ACTIVE');

        DB::table('account_profiles')->insert([
            'account_id' => $accountId,
            'display_name' => 'Nama Lama',
            'display_name_changed_at' => now()->subDays(10),
            'profile_photo' => null,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->putJson('/api/v1/account/profile', [
            'display_name' => 'Nama Baharu',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Nama paparan hanya boleh diubah sekali setiap 30 hari.',
            ]);

        $this->assertDatabaseHas('account_profiles', [
            'account_id' => $accountId,
            'display_name' => 'Nama Lama',
        ]);
    }

    public function test_profile_photo_can_be_updated_without_changing_display_name(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('ACTIVE');

        $changedAt = now()->subDays(10);

        DB::table('account_profiles')->insert([
            'account_id' => $accountId,
            'display_name' => 'Nama Lama',
            'display_name_changed_at' => $changedAt,
            'profile_photo' => 'profiles/lama.jpg',
            'created_at' => now()->subDays(10),
            'updated_at' => $changedAt,
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->putJson('/api/v1/account/profile', [
            'profile_photo' => 'profiles/baharu.jpg',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Profil berjaya dikemas kini.',
                'data' => [
                    'account_id' => $accountId,
                ],
            ]);

        $profile = DB::table('account_profiles')
            ->where('account_id', $accountId)
            ->first();

        $this->assertSame('Nama Lama', $profile->display_name);
        $this->assertSame(
            'profiles/baharu.jpg',
            $profile->profile_photo
        );
        $this->assertSame(
            $changedAt->format('Y-m-d H:i:s'),
            $profile->display_name_changed_at
        );
    }

    public function test_profile_update_requires_authentication(): void
    {
        $response = $this->putJson('/api/v1/account/profile', [
            'display_name' => 'Nama Baharu',
        ]);

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
            'device_identifier' => 'profile-update-test-device',
            'platform' => 'ANDROID',
            'device_name' => 'Profile Update Test Device',
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
