<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return ApiResponse::error(
                message: 'Sesi tidak sah.',
                statusCode: 401
            );
        }

        $tokenHash = hash('sha256', $token);

        $session = DB::table('app_sessions')
            ->where('token_hash', $tokenHash)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $session) {
            return ApiResponse::error(
                message: 'Sesi tidak sah atau telah tamat.',
                statusCode: 401
            );
        }

        $account = Account::query()
            ->where('id', $session->account_id)
            ->whereIn('status', ['ACTIVE', 'SUSPENDED', 'DEACTIVATED'])
            ->first();

        if (! $account) {
            return ApiResponse::error(
                message: 'Akaun tidak tersedia.',
                statusCode: 401
            );
        }

        $request->attributes->set('account', $account);
        $request->attributes->set('app_session', $session);

        DB::table('app_sessions')
            ->where('id', $session->id)
            ->update([
                'last_used_at' => now(),
            ]);

        return $next($request);
    }
}
