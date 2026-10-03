<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_identifier_password_app_and_device_id(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response
            ->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Maklumat log masuk tidak lengkap.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ]);
    }

    public function test_login_with_valid_credentials_returns_session_token(): void
    {
        [$accountId, $deviceId] = $this->createLoginAccount();

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'security@example.com',
            'password' => 'Password1',
            'app' => 'USER',
            'device_id' => $deviceId,
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Log masuk berjaya.',
                'data' => [
                    'account_id' => $accountId,
                ],
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'account_id',
                    'session_id',
                    'token',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.session_id'));
        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('app_sessions', [
            'account_id' => $accountId,
            'device_id' => $deviceId,
            'app' => 'USER',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_suspended_account_can_login(): void
    {
        [$accountId, $deviceId] = $this->createLoginAccount('SUSPENDED');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'security@example.com',
            'password' => 'Password1',
            'app' => 'USER',
            'device_id' => $deviceId,
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Log masuk berjaya.',
                'data' => [
                    'account_id' => $accountId,
                ],
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'account_id',
                    'session_id',
                    'token',
                ],
            ]);
    }

    public function test_deactivated_account_cannot_login(): void
    {
        [$accountId, $deviceId] = $this->createLoginAccount('DEACTIVATED');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'security@example.com',
            'password' => 'Password1',
            'app' => 'USER',
            'device_id' => $deviceId,
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Akaun tidak tersedia.',
                'data' => null,
            ]);

        $this->assertDatabaseMissing('app_sessions', [
            'account_id' => $accountId,
            'app' => 'USER',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_deleted_account_cannot_login(): void
    {
        [$accountId, $deviceId] = $this->createLoginAccount('DELETED');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'security@example.com',
            'password' => 'Password1',
            'app' => 'USER',
            'device_id' => $deviceId,
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Akaun tidak tersedia.',
                'data' => null,
            ]);

        $this->assertDatabaseMissing('app_sessions', [
            'account_id' => $accountId,
            'app' => 'USER',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_failed_login_reaches_level_one_after_three_attempts(): void
    {
        [$accountId, $deviceId] = $this->createLoginAccount();

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'identifier' => 'security@example.com',
                'password' => 'WrongPassword1',
                'app' => 'USER',
                'device_id' => $deviceId,
            ]);

            $response
                ->assertStatus(401)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Maklumat log masuk tidak sah.',
                ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'security@example.com',
            'password' => 'WrongPassword1',
            'app' => 'USER',
            'device_id' => $deviceId,
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Maklumat log masuk tidak sah.',
            ]);

        $security = DB::table('account_security')
            ->where('account_id', $accountId)
            ->first();

        $this->assertSame(3, $security->failed_attempts);
        $this->assertSame(1, $security->security_level);
        $this->assertNotNull($security->locked_until);
        $this->assertFalse((bool) $security->admin_review_required);
    }

    public function test_failed_login_reaches_level_two_after_six_attempts(): void
    {
        [$accountId, $deviceId] = $this->createLoginAccount();

        DB::table('account_security')->insert([
            'account_id' => $accountId,
            'failed_attempts' => 5,
            'security_level' => 1,
            'locked_until' => now()->subMinute(),
            'admin_review_required' => false,
            'admin_reviewed_at' => null,
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'security@example.com',
            'password' => 'WrongPassword1',
            'app' => 'USER',
            'device_id' => $deviceId,
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Maklumat log masuk tidak sah.',
            ]);

        $security = DB::table('account_security')
            ->where('account_id', $accountId)
            ->first();

        $this->assertSame(6, $security->failed_attempts);
        $this->assertSame(2, $security->security_level);
        $this->assertNotNull($security->locked_until);
        $this->assertFalse((bool) $security->admin_review_required);
    }

    public function test_failed_login_reaches_level_three_after_nine_attempts(): void
    {
        [$accountId, $deviceId] = $this->createLoginAccount();

        DB::table('account_security')->insert([
            'account_id' => $accountId,
            'failed_attempts' => 8,
            'security_level' => 2,
            'locked_until' => now()->subMinute(),
            'admin_review_required' => false,
            'admin_reviewed_at' => null,
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'security@example.com',
            'password' => 'WrongPassword1',
            'app' => 'USER',
            'device_id' => $deviceId,
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Maklumat log masuk tidak sah.',
            ]);

        $security = DB::table('account_security')
            ->where('account_id', $accountId)
            ->first();

        $this->assertSame(9, $security->failed_attempts);
        $this->assertSame(3, $security->security_level);
        $this->assertNull($security->locked_until);
        $this->assertTrue((bool) $security->admin_review_required);
    }

    public function test_login_is_blocked_while_level_one_lock_is_active(): void
    {
        [$accountId, $deviceId] = $this->createLoginAccount();

        DB::table('account_security')->insert([
            'account_id' => $accountId,
            'failed_attempts' => 3,
            'security_level' => 1,
            'locked_until' => now()->addMinutes(30),
            'admin_review_required' => false,
            'admin_reviewed_at' => null,
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'security@example.com',
            'password' => 'Password1',
            'app' => 'USER',
            'device_id' => $deviceId,
        ]);

        $response
            ->assertStatus(423)
            ->assertJson([
                'status' => 'error',
                'message' => 'Log masuk disekat sementara.',
            ]);
    }

    public function test_level_three_blocks_login_and_requires_admin_review(): void
    {
        [$accountId, $deviceId] = $this->createLoginAccount();

        DB::table('account_security')->insert([
            'account_id' => $accountId,
            'failed_attempts' => 9,
            'security_level' => 3,
            'locked_until' => null,
            'admin_review_required' => true,
            'admin_reviewed_at' => null,
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'security@example.com',
            'password' => 'Password1',
            'app' => 'USER',
            'device_id' => $deviceId,
        ]);

        $response
            ->assertStatus(423)
            ->assertJson([
                'status' => 'error',
                'message' => 'Log masuk disekat dan memerlukan semakan Admin.',
            ]);
    }

    private function createLoginAccount(string $status = 'ACTIVE'): array
    {
        $accountId = (string) Str::uuid7();
        $deviceId = (string) Str::uuid7();
        $contactId = (string) Str::uuid7();
        $now = now();

        DB::table('accounts')->insert([
            'id' => $accountId,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
            'deactivated_at' => $status === 'DEACTIVATED' ? $now : null,
            'deleted_at' => $status === 'DELETED' ? $now : null,
        ]);

        DB::table('account_contacts')->insert([
            'id' => $contactId,
            'account_id' => $accountId,
            'type' => 'EMAIL',
            'value' => 'security@example.com',
            'status' => 'ACTIVE',
            'is_verified' => true,
            'verified_at' => $now,
            'released_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('account_passwords')->insert([
            'account_id' => $accountId,
            'password_hash' => Hash::make('Password1'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('account_devices')->insert([
            'id' => $deviceId,
            'account_id' => $accountId,
            'device_identifier' => 'security-device-001',
            'platform' => 'ANDROID',
            'device_name' => 'Security Test Device',
            'last_seen_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$accountId, $deviceId];
    }
}
