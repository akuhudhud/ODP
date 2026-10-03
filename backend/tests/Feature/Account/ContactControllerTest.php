<?php

namespace Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_account_can_get_active_contacts(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('ACTIVE');

        DB::table('account_contacts')->insert([
            'id' => (string) Str::uuid7(),
            'account_id' => $accountId,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => true,
            'verified_at' => now(),
            'released_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_contacts')->insert([
            'id' => (string) Str::uuid7(),
            'account_id' => $accountId,
            'type' => 'EMAIL',
            'value' => 'test@example.com',
            'status' => 'RELEASED',
            'is_verified' => true,
            'verified_at' => now(),
            'released_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/account/contacts');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Maklumat contact berjaya diperoleh.',
                'data' => [
                    'account_id' => $accountId,
                ],
            ]);

        $this->assertCount(
            1,
            $response->json('data.contacts')
        );

        $this->assertSame(
            'PHONE',
            $response->json('data.contacts.0.type')
        );

        $this->assertSame(
            '+60123456789',
            $response->json('data.contacts.0.value')
        );
    }

    public function test_suspended_account_can_get_active_contacts(): void
    {
        [$accountId, $token] = $this->createAuthenticatedAccount('SUSPENDED');

        DB::table('account_contacts')->insert([
            'id' => (string) Str::uuid7(),
            'account_id' => $accountId,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => true,
            'verified_at' => now(),
            'released_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/account/contacts');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Maklumat contact berjaya diperoleh.',
                'data' => [
                    'account_id' => $accountId,
                ],
            ]);

        $this->assertCount(
            1,
            $response->json('data.contacts')
        );
    }

    public function test_account_contacts_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/account/contacts');

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
            'device_identifier' => 'contact-test-device',
            'platform' => 'ANDROID',
            'device_name' => 'Contact Test Device',
            'last_seen_at' => null,
            'updated_at' => $now,
            'created_at' => $now,
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
