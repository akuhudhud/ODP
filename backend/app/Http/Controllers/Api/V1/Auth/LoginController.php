<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\ApiSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function __construct(
        private readonly ApiSessionService $sessionService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
            'app' => ['required', 'string', 'in:USER,RUNNER'],
            'device_id' => ['required', 'string'],
        ]);

        $identifier = $validated['identifier'];
        $app = $validated['app'];
        $deviceId = $validated['device_id'];

        $contact = DB::table('account_contacts')
            ->where('value', $identifier)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $contact) {
            return ApiResponse::error(
                message: 'Maklumat log masuk tidak sah.',
                statusCode: 401
            );
        }

        $account = DB::table('accounts')
            ->where('id', $contact->account_id)
            ->first();

        if (! $account || $account->status !== 'ACTIVE') {
            return ApiResponse::error(
                message: 'Akaun tidak tersedia.',
                statusCode: 401
            );
        }

        $security = DB::table('account_security')
            ->where('account_id', $account->id)
            ->first();

        if ($security?->admin_review_required) {
            return ApiResponse::error(
                message: 'Log masuk disekat dan memerlukan semakan Admin.',
                statusCode: 423
            );
        }

        if (
            $security?->locked_until !== null &&
            now()->lessThan($security->locked_until)
        ) {
            return ApiResponse::error(
                message: 'Log masuk disekat sementara.',
                statusCode: 423
            );
        }

        $password = DB::table('account_passwords')
            ->where('account_id', $account->id)
            ->first();

        if (
            ! $password ||
            ! Hash::check($validated['password'], $password->password_hash)
        ) {
            $this->recordFailedLogin($account->id, $security);

            return ApiResponse::error(
                message: 'Maklumat log masuk tidak sah.',
                statusCode: 401
            );
        }

        $device = DB::table('account_devices')
            ->where('id', $deviceId)
            ->where('account_id', $account->id)
            ->first();

        if (! $device) {
            return ApiResponse::error(
                message: 'Peranti tidak sah.',
                statusCode: 401
            );
        }

        DB::table('account_security')
            ->where('account_id', $account->id)
            ->update([
                'failed_attempts' => 0,
                'security_level' => 0,
                'locked_until' => null,
                'admin_review_required' => false,
                'updated_at' => now(),
            ]);

        DB::table('account_devices')
            ->where('id', $device->id)
            ->update([
                'last_seen_at' => now(),
                'updated_at' => now(),
            ]);

        $session = $this->sessionService->create(
            accountId: $account->id,
            app: $app,
            deviceId: $device->id
        );

        return ApiResponse::success(
            message: 'Log masuk berjaya.',
            data: [
                'account_id' => $account->id,
                'session_id' => $session['session_id'],
                'token' => $session['token'],
            ]
        );
    }

    private function recordFailedLogin(
        string $accountId,
        ?object $security
    ): void {
        $failedAttempts = ($security?->failed_attempts ?? 0) + 1;

        $securityLevel = $security?->security_level ?? 0;
        $adminReviewRequired = false;
        $lockedUntil = null;

        if ($failedAttempts >= 9) {
            $securityLevel = 3;
            $adminReviewRequired = true;
        } elseif ($failedAttempts >= 6) {
            $securityLevel = 2;
            $lockedUntil = now()->addHour();
        } elseif ($failedAttempts >= 3) {
            $securityLevel = 1;
            $lockedUntil = now()->addMinutes(30);
        }

        DB::table('account_security')->updateOrInsert(
            ['account_id' => $accountId],
            [
                'failed_attempts' => $failedAttempts,
                'security_level' => $securityLevel,
                'locked_until' => $lockedUntil,
                'admin_review_required' => $adminReviewRequired,
                'updated_at' => now(),
            ]
        );
    }
}
