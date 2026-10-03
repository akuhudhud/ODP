<?php

namespace App\Services\Otp;

use App\Models\Account;
use App\Models\AccountContact;
use App\Models\OtpChallenge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class OtpChallengeService
{
    public const PURPOSE_REGISTER = 'REGISTER';
    public const PURPOSE_VERIFY_PHONE = 'VERIFY_PHONE';
    public const PURPOSE_VERIFY_EMAIL = 'VERIFY_EMAIL';
    public const PURPOSE_CHANGE_PHONE = 'CHANGE_PHONE';
    public const PURPOSE_CHANGE_EMAIL = 'CHANGE_EMAIL';
    public const PURPOSE_ACCOUNT_RECOVERY = 'ACCOUNT_RECOVERY';

    private const CODE_LENGTH = 6;
    private const EXPIRY_MINUTES = 5;
    private const MAX_ATTEMPTS = 3;
    private const RESEND_COOLDOWN_MINUTES = 5;
    private const MAX_RESENDS = 3;
    private const RESEND_LOCK_HOURS = 24;

    /**
     * Create a new OTP challenge.
     *
     * The plaintext OTP is returned only to the caller and is never stored.
     * When a previous challenge exists for the same contact and purpose,
     * it is invalidated before the new challenge is created.
     */
    public function issue(
        AccountContact $contact,
        string $purpose,
        ?Account $account = null
    ): array {
        $this->validatePurpose($purpose);

        return DB::transaction(function () use ($contact, $purpose, $account): array {
            $now = Carbon::now();

            $latest = OtpChallenge::query()
                ->where('contact_id', $contact->id)
                ->where('purpose', $purpose)
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if ($latest !== null) {
                if (
                    $latest->last_sent_at !== null
                    && $latest->last_sent_at->gt(
                        $now->copy()->subMinutes(self::RESEND_COOLDOWN_MINUTES)
                    )
                ) {
                    throw new RuntimeException(
                        'OTP resend is temporarily locked. Please wait before requesting another OTP.'
                    );
                }

                if (
                    $latest->resend_count >= self::MAX_RESENDS
                    && $latest->last_sent_at->gt(
                        $now->copy()->subHours(self::RESEND_LOCK_HOURS)
                    )
                ) {
                    throw new RuntimeException(
                        'OTP resend limit reached. Please try again later.'
                    );
                }

                $resendCount = $latest->resend_count + 1;

                $latest->update([
                    'invalidated_at' => $now,
                ]);
            } else {
                $resendCount = 0;
            }

            $code = $this->generateCode();

            $challenge = OtpChallenge::create([
                'id' => (string) Str::uuid7(),
                'account_id' => $account?->id ?? $contact->account_id,
                'contact_id' => $contact->id,
                'purpose' => $purpose,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'resend_count' => $resendCount,
                'expires_at' => $now->copy()->addMinutes(self::EXPIRY_MINUTES),
                'last_sent_at' => $now,
                'created_at' => $now,
            ]);

            return [
                'challenge' => $challenge,
                'code' => $code,
            ];
        });
    }

    /**
     * Verify an OTP challenge.
     */
    public function verify(OtpChallenge $challenge, string $code): bool
    {
        return DB::transaction(function () use ($challenge, $code): bool {
            $challenge = OtpChallenge::query()
                ->lockForUpdate()
                ->findOrFail($challenge->id);

            if ($challenge->consumed_at !== null) {
                return false;
            }

            if ($challenge->invalidated_at !== null) {
                return false;
            }

            if ($challenge->expires_at->isPast()) {
                return false;
            }

            if ($challenge->attempts >= self::MAX_ATTEMPTS) {
                return false;
            }

            $challenge->increment('attempts');

            if (!Hash::check($code, $challenge->code_hash)) {
                return false;
            }

            $challenge->update([
                'consumed_at' => Carbon::now(),
            ]);

            return true;
        });
    }

    private function generateCode(): string
    {
        return str_pad(
            (string) random_int(0, 999999),
            self::CODE_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }

    private function validatePurpose(string $purpose): void
    {
        $allowedPurposes = [
            self::PURPOSE_REGISTER,
            self::PURPOSE_VERIFY_PHONE,
            self::PURPOSE_VERIFY_EMAIL,
            self::PURPOSE_CHANGE_PHONE,
            self::PURPOSE_CHANGE_EMAIL,
            self::PURPOSE_ACCOUNT_RECOVERY,
        ];

        if (!in_array($purpose, $allowedPurposes, true)) {
            throw new RuntimeException('Invalid OTP purpose.');
        }
    }
}
