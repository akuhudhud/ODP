<?php

namespace Tests\Unit\Services\Otp;

use App\Models\Account;
use App\Models\AccountContact;
use App\Models\OtpChallenge;
use App\Services\Otp\OtpChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpChallengeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_creates_six_digit_otp_and_stores_only_hash(): void
    {
        $account = Account::create([
            'status' => 'ACTIVE',
        ]);

        $contact = AccountContact::create([
            'account_id' => $account->id,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => false,
        ]);

        $service = new OtpChallengeService();

        $result = $service->issue(
            $contact,
            OtpChallengeService::PURPOSE_VERIFY_PHONE
        );

        $this->assertArrayHasKey('challenge', $result);
        $this->assertArrayHasKey('code', $result);

        $challenge = $result['challenge'];
        $code = $result['code'];

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, $challenge->code_hash);
        $this->assertTrue(Hash::check($code, $challenge->code_hash));

        $this->assertSame(
            OtpChallengeService::PURPOSE_VERIFY_PHONE,
            $challenge->purpose
        );
        $this->assertSame(0, $challenge->attempts);
        $this->assertSame(0, $challenge->resend_count);
        $this->assertNull($challenge->consumed_at);
        $this->assertNull($challenge->invalidated_at);
    }

    public function test_verify_accepts_correct_code_and_consumes_challenge(): void
    {
        $account = Account::create([
            'status' => 'ACTIVE',
        ]);

        $contact = AccountContact::create([
            'account_id' => $account->id,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => false,
        ]);

        $service = new OtpChallengeService();

        $result = $service->issue(
            $contact,
            OtpChallengeService::PURPOSE_VERIFY_PHONE
        );

        $challenge = $result['challenge'];

        $verified = $service->verify(
            $challenge,
            $result['code']
        );

        $this->assertTrue($verified);

        $challenge->refresh();

        $this->assertNotNull($challenge->consumed_at);
        $this->assertSame(1, $challenge->attempts);
    }

    public function test_verify_rejects_wrong_code_and_counts_attempt(): void
    {
        $account = Account::create([
            'status' => 'ACTIVE',
        ]);

        $contact = AccountContact::create([
            'account_id' => $account->id,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => false,
        ]);

        $service = new OtpChallengeService();

        $result = $service->issue(
            $contact,
            OtpChallengeService::PURPOSE_VERIFY_PHONE
        );

        $challenge = $result['challenge'];

        $verified = $service->verify($challenge, '000000');

        $this->assertFalse($verified);

        $challenge->refresh();

        $this->assertSame(1, $challenge->attempts);
        $this->assertNull($challenge->consumed_at);
    }

    public function test_verify_rejects_expired_challenge(): void
    {
        $account = Account::create([
            'status' => 'ACTIVE',
        ]);

        $contact = AccountContact::create([
            'account_id' => $account->id,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => false,
        ]);

        $service = new OtpChallengeService();

        $result = $service->issue(
            $contact,
            OtpChallengeService::PURPOSE_VERIFY_PHONE
        );

        $challenge = $result['challenge'];

        $challenge->update([
            'expires_at' => Carbon::now()->subSecond(),
        ]);

        $this->assertFalse(
            $service->verify($challenge, $result['code'])
        );

        $challenge->refresh();

        $this->assertSame(0, $challenge->attempts);
        $this->assertNull($challenge->consumed_at);
    }

    public function test_new_issue_invalidates_previous_challenge_for_same_contact_and_purpose(): void
    {
        $account = Account::create([
            'status' => 'ACTIVE',
        ]);

        $contact = AccountContact::create([
            'account_id' => $account->id,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => false,
        ]);

        $service = new OtpChallengeService();

        $first = $service->issue(
            $contact,
            OtpChallengeService::PURPOSE_VERIFY_PHONE
        );

        Carbon::setTestNow(
            Carbon::now()->addMinutes(6)
        );

        $second = $service->issue(
            $contact,
            OtpChallengeService::PURPOSE_VERIFY_PHONE
        );

        $first['challenge']->refresh();

        $this->assertNotNull($first['challenge']->invalidated_at);
        $this->assertSame(1, $second['challenge']->resend_count);

        $this->assertFalse(
            $service->verify(
                $first['challenge'],
                $first['code']
            )
        );

        $this->assertTrue(
            $service->verify(
                $second['challenge'],
                $second['code']
            )
        );

        Carbon::setTestNow();
    }
}
