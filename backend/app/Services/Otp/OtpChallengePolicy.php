<?php

namespace App\Services\Otp;

use App\Models\Account;
use App\Models\AccountContact;

class OtpChallengePolicy
{
    /**
     * Determine whether an OTP purpose is allowed for the supplied context.
     *
     * The policy layer controls authorization context only.
     * OTP generation, hashing, expiry, resend and verification remain
     * responsibilities of OtpChallengeService.
     */
    public function allows(
        string $purpose,
        ?Account $account,
        AccountContact $contact,
        bool $authenticated
    ): bool {
        if ($account !== null && $contact->account_id !== $account->id) {
            return false;
        }

        return match ($purpose) {
            OtpChallengeService::PURPOSE_REGISTER =>
                ! $authenticated && $account === null,

            OtpChallengeService::PURPOSE_VERIFY_PHONE =>
                $authenticated && $account !== null,

            OtpChallengeService::PURPOSE_VERIFY_EMAIL =>
                $authenticated && $account !== null,

            OtpChallengeService::PURPOSE_CHANGE_PHONE =>
                $authenticated && $account !== null,

            OtpChallengeService::PURPOSE_CHANGE_EMAIL =>
                $authenticated && $account !== null,

            OtpChallengeService::PURPOSE_ACCOUNT_RECOVERY =>
                ! $authenticated && $account === null,

            default => false,
        };
    }
}
