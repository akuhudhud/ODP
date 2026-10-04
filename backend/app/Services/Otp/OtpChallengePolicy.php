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
        if ($account === null) {
            return false;
        }

        if ($contact->account_id !== $account->id) {
            return false;
        }

        return match ($purpose) {
            OtpChallengeService::PURPOSE_REGISTER =>
                ! $authenticated
                && $account->status === 'ACTIVE'
                && $contact->type === 'PHONE'
                && $contact->status === 'PENDING'
                && ! $contact->is_verified,

            OtpChallengeService::PURPOSE_VERIFY_PHONE =>
                $authenticated
                && $account->status === 'ACTIVE'
                && $contact->type === 'PHONE'
                && $contact->status === 'PENDING'
                && ! $contact->is_verified,

            OtpChallengeService::PURPOSE_VERIFY_EMAIL =>
                $authenticated
                && $account->status === 'ACTIVE'
                && $contact->type === 'EMAIL'
                && $contact->status === 'PENDING'
                && ! $contact->is_verified,

            OtpChallengeService::PURPOSE_CHANGE_PHONE =>
                $authenticated
                && $account->status === 'ACTIVE'
                && $contact->type === 'PHONE'
                && $contact->status === 'PENDING'
                && ! $contact->is_verified,

            OtpChallengeService::PURPOSE_CHANGE_EMAIL =>
                $authenticated
                && $account->status === 'ACTIVE'
                && $contact->type === 'EMAIL'
                && $contact->status === 'PENDING'
                && ! $contact->is_verified,

            OtpChallengeService::PURPOSE_ACCOUNT_RECOVERY =>
                ! $authenticated
                && $account->status !== 'DELETED'
                && $contact->type === 'EMAIL'
                && $contact->status === 'ACTIVE'
                && $contact->is_verified,

            default => false,
        };
    }
}
