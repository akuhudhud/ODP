<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogoutController
{
    public function __invoke(Request $request)
    {
        $session = $request->attributes->get('app_session');

        if (! $session) {
            return ApiResponse::error(
                message: 'Sesi tidak sah.',
                statusCode: 401
            );
        }

        DB::table('app_sessions')
            ->where('id', $session->id)
            ->where('status', 'ACTIVE')
            ->update([
                'status' => 'REVOKED',
                'revoked_at' => now(),
            ]);

        return ApiResponse::success(
            message: 'Anda telah log keluar.',
            data: null
        );
    }
}
