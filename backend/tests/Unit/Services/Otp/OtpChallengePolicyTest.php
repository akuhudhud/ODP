<?php

namespace Tests\Unit\Services\Otp;

use App\Models\Account;
use App\Models\AccountContact;
use App\Services\Otp\OtpChallengePolicy;
use App\Services\Otp\OtpChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpChallengePolicyTest extends TestCase
{
    use RefreshDatabase;

    private OtpChallengePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new OtpChallengePolicy();
    }

    public function test_register_allows_pending_unverified_phone_for_active_account_when_unauthenticated(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'PENDING',
            false
        );

        $this->assertTrue(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_REGISTER,
                $account,
                $contact,
                false
            )
        );
    }

    public function test_register_rejects_authenticated_context(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'PENDING',
            false
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_REGISTER,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_verify_phone_allows_pending_unverified_phone_for_active_authenticated_account(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'PENDING',
            false
        );

        $this->assertTrue(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_VERIFY_PHONE,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_verify_email_allows_pending_unverified_email_for_active_authenticated_account(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'EMAIL',
            'PENDING',
            false
        );

        $this->assertTrue(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_VERIFY_EMAIL,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_verify_phone_rejects_email_contact(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'EMAIL',
            'PENDING',
            false
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_VERIFY_PHONE,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_verify_email_rejects_phone_contact(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'PENDING',
            false
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_VERIFY_EMAIL,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_change_phone_allows_pending_unverified_phone_for_active_authenticated_account(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'PENDING',
            false
        );

        $this->assertTrue(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_CHANGE_PHONE,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_change_email_allows_pending_unverified_email_for_active_authenticated_account(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'EMAIL',
            'PENDING',
            false
        );

        $this->assertTrue(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_CHANGE_EMAIL,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_change_contact_rejects_suspended_account(): void
    {
        $account = $this->createAccount('SUSPENDED');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'PENDING',
            false
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_CHANGE_PHONE,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_policy_rejects_contact_belonging_to_another_account(): void
    {
        $account = $this->createAccount('ACTIVE');
        $otherAccount = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $otherAccount,
            'PHONE',
            'PENDING',
            false
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_VERIFY_PHONE,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_policy_rejects_verified_pending_contact(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'PENDING',
            true
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_VERIFY_PHONE,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_policy_rejects_active_unverified_contact_for_change_flow(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'ACTIVE',
            false
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_CHANGE_PHONE,
                $account,
                $contact,
                true
            )
        );
    }

    public function test_account_recovery_allows_verified_active_email_for_active_account_when_unauthenticated(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'EMAIL',
            'ACTIVE',
            true
        );

        $this->assertTrue(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_ACCOUNT_RECOVERY,
                $account,
                $contact,
                false
            )
        );
    }

    public function test_account_recovery_allows_verified_active_email_for_suspended_account_when_unauthenticated(): void
    {
        $account = $this->createAccount('SUSPENDED');

        $contact = $this->createContact(
            $account,
            'EMAIL',
            'ACTIVE',
            true
        );

        $this->assertTrue(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_ACCOUNT_RECOVERY,
                $account,
                $contact,
                false
            )
        );
    }

    public function test_account_recovery_rejects_deleted_account(): void
    {
        $account = $this->createAccount('DELETED');

        $contact = $this->createContact(
            $account,
            'EMAIL',
            'ACTIVE',
            true
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_ACCOUNT_RECOVERY,
                $account,
                $contact,
                false
            )
        );
    }

    public function test_account_recovery_rejects_phone_contact(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'ACTIVE',
            true
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_ACCOUNT_RECOVERY,
                $account,
                $contact,
                false
            )
        );
    }

    public function test_policy_rejects_missing_account(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'PENDING',
            false
        );

        $this->assertFalse(
            $this->policy->allows(
                OtpChallengeService::PURPOSE_VERIFY_PHONE,
                null,
                $contact,
                true
            )
        );
    }

    public function test_policy_rejects_unknown_purpose(): void
    {
        $account = $this->createAccount('ACTIVE');

        $contact = $this->createContact(
            $account,
            'PHONE',
            'PENDING',
            false
        );

        $this->assertFalse(
            $this->policy->allows(
                'UNKNOWN_PURPOSE',
                $account,
                $contact,
                true
            )
        );
    }

    private function createAccount(string $status): Account
    {
        return Account::create([
            'status' => $status,
        ]);
    }

    private function createContact(
        Account $account,
        string $type,
        string $status,
        bool $isVerified
    ): AccountContact {
        $value = $type === 'PHONE'
            ? '+6012' . random_int(1000000, 9999999)
            : 'test' . random_int(1000000, 9999999) . '@example.com';

        return AccountContact::create([
            'account_id' => $account->id,
            'type' => $type,
            'value' => $value,
            'status' => $status,
            'is_verified' => $isVerified,
        ]);
    }
}
