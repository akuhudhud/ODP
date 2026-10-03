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
        $accountId = (string) Str::uuid7();
        $deviceId = (string) Str::uuid7();
        $contactId = (string) Str::uuid7();
        $now = now();

        DB::table('accounts')->insert([
            'id' => $accountId,
            'status' => 'ACTIVE',
            'created_at' => $now,
            'updated_at' => $now,
            'deactivated_at' => null,
            'deleted_at' => null,
        ]);

        DB::table('account_contacts')->insert([
            'id' => $contactId,
            'account_id' => $accountId,
            'type' => 'EMAIL',
            'value' => 'test@example.com',
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
            'device_identifier' => 'test-device-001',
            'platform' => 'ANDROID',
            'device_name' => 'Test Device',
            'last_seen_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test@example.com',
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
}
