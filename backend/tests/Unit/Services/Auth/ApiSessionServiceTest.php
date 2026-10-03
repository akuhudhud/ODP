<?php

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\ApiSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_new_session_revokes_previous_active_session_for_same_account_and_app(): void
    {
        $accountId = (string) Str::uuid7();
        $now = now();

        DB::table('accounts')->insert([
            'id' => $accountId,
            'status' => 'ACTIVE',
            'created_at' => $now,
            'updated_at' => $now,
            'deactivated_at' => null,
            'deleted_at' => null,
        ]);

        $service = new ApiSessionService();

        $firstSession = $service->create(
            accountId: $accountId,
            app: 'USER'
        );

        $secondSession = $service->create(
            accountId: $accountId,
            app: 'USER'
        );

        $firstRecord = DB::table('app_sessions')
            ->where('id', $firstSession['session_id'])
            ->first();

        $secondRecord = DB::table('app_sessions')
            ->where('id', $secondSession['session_id'])
            ->first();

        $this->assertSame('REVOKED', $firstRecord->status);
        $this->assertNotNull($firstRecord->revoked_at);

        $this->assertSame('ACTIVE', $secondRecord->status);
        $this->assertNull($secondRecord->revoked_at);

        $this->assertNotSame(
            $firstSession['token'],
            $secondSession['token']
        );

        $this->assertSame(
            hash('sha256', $firstSession['token']),
            $firstRecord->token_hash
        );

        $this->assertSame(
            hash('sha256', $secondSession['token']),
            $secondRecord->token_hash
        );
    }
}
