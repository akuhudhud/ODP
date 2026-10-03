<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiSessionService
{
    public function create(
        string $accountId,
        string $app,
        ?string $deviceId = null
    ): array {
        DB::table('app_sessions')
            ->where('account_id', $accountId)
            ->where('app', $app)
            ->where('status', 'ACTIVE')
            ->update([
                'status' => 'REVOKED',
                'revoked_at' => now(),
            ]);

        $sessionId = (string) Str::uuid7();
        $rawToken = bin2hex(random_bytes(32));
        $now = now();

        DB::table('app_sessions')->insert([
            'id' => $sessionId,
            'account_id' => $accountId,
            'device_id' => $deviceId,
            'app' => $app,
            'token_hash' => hash('sha256', $rawToken),
            'status' => 'ACTIVE',
            'created_at' => $now,
            'last_used_at' => null,
            'revoked_at' => null,
            'expires_at' => null,
        ]);

        return [
            'session_id' => $sessionId,
            'token' => $rawToken,
        ];
    }
}
