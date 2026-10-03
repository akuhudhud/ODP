<?php

namespace App\Services\Otp;

use App\Models\AccountContact;
use App\Models\OtpCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class OtpService
{
    private const OTP_LENGTH = 6;

    private const OTP_TTL_MINUTES = 5;

    private const MAX_ATTEMPTS = 3;

    private const RESEND_COOLDOWN_MINUTES = 5;

    public function request(
        AccountContact $contact,
        string $purpose,
        string $channel
    ): array {
        return DB::transaction(function () use (
            $contact,
            $purpose,
            $channel
        ): array {
            $latest = OtpCode::query()
                ->where('account_id', $contact->account_id)
                ->where('contact_id', $contact->id)
                ->where('purpose', $purpose)
                ->where('status', 'ACTIVE')
                ->latest('created_at')
                ->first();

            if (
                $latest !== null
                && $latest->last_sent_at !== null
                && now()->lessThan(
                    $latest->last_sent_at->copy()->addMinutes(
                        self::RESEND_COOLDOWN_MINUTES
                    )
                )
            ) {
                throw new RuntimeException(
                    'OTP hanya boleh diminta semula selepas 5 minit.'
                );
            }

            OtpCode::query()
                ->where('account_id', $contact->account_id)
                ->where('contact_id', $contact->id)
                ->where('purpose', $purpose)
                ->where('status', 'ACTIVE')
                ->update([
                    'status' => 'INVALIDATED',
                    'invalidated_at' => now(),
                    'updated_at' => now(),
                ]);

            $code = str_pad(
                (string) random_int(0, 999999),
                self::OTP_LENGTH,
                '0',
                STR_PAD_LEFT
            );

            $otp = OtpCode::query()->create([
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'purpose' => $purpose,
                'channel' => $channel,
                'code_hash' => Hash::make($code),
                'status' => 'ACTIVE',
                'attempts' => 0,
                'expires_at' => now()->addMinutes(
                    self::OTP_TTL_MINUTES
                ),
                'last_sent_at' => now(),
            ]);

            return [
                'otp' => $otp,
                'code' => $code,
            ];
        });
    }

    public function verify(
        OtpCode $otp,
        string $code
    ): bool {
        if ($otp->status !== 'ACTIVE') {
            return false;
        }

        if ($otp->expires_at->isPast()) {
            $otp->update([
                'status' => 'EXPIRED',
                'updated_at' => now(),
            ]);

            return false;
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->update([
                'status' => 'FAILED',
                'updated_at' => now(),
            ]);

            return false;
        }

        if (!Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            $otp->refresh();

            if ($otp->attempts >= self::MAX_ATTEMPTS) {
                $otp->update([
                    'status' => 'FAILED',
                    'updated_at' => now(),
                ]);
            }

            return false;
        }

        $otp->update([
            'status' => 'VERIFIED',
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }
}
