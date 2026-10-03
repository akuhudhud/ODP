<?php

namespace Tests\Unit\Services\Otp;

use App\Models\Account;
use App\Models\AccountContact;
use App\Models\OtpCode;
use App\Services\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_creates_six_digit_otp_with_five_minute_expiry(): void
    {
        [$account, $contact] = $this->createPhoneContact();

        $result = app(OtpService::class)->request(
            $contact,
            'VERIFY_PHONE',
            'WHATSAPP'
        );

        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            $result['code']
        );

        $this->assertInstanceOf(
            OtpCode::class,
            $result['otp']
        );

        $this->assertSame(
            $account->id,
            $result['otp']->account_id
        );

        $this->assertSame(
            $contact->id,
            $result['otp']->contact_id
        );

        $this->assertSame(
            'VERIFY_PHONE',
            $result['otp']->purpose
        );

        $this->assertSame(
            'WHATSAPP',
            $result['otp']->channel
        );

        $this->assertSame(
            'ACTIVE',
            $result['otp']->status
        );

        $this->assertSame(
            0,
            $result['otp']->attempts
        );

        $this->assertTrue(
            $result['otp']->expires_at->between(
                now()->addMinutes(4)->addSeconds(50),
                now()->addMinutes(5)->addSeconds(10)
            )
        );
    }

    public function test_request_rejects_resend_within_five_minute_cooldown(): void
    {
        [, $contact] = $this->createPhoneContact();

        $service = app(OtpService::class);

        $service->request(
            $contact,
            'VERIFY_PHONE',
            'WHATSAPP'
        );

        $this->expectException(RuntimeException::class);

        $service->request(
            $contact,
            'VERIFY_PHONE',
            'WHATSAPP'
        );
    }

    public function test_new_otp_invalidates_previous_active_otp(): void
    {
        [, $contact] = $this->createPhoneContact();

        $service = app(OtpService::class);

        $first = $service->request(
            $contact,
            'VERIFY_PHONE',
            'WHATSAPP'
        );

        OtpCode::query()
            ->whereKey($first['otp']->id)
            ->update([
                'last_sent_at' => now()->subMinutes(6),
            ]);

        $second = $service->request(
            $contact,
            'VERIFY_PHONE',
            'WHATSAPP'
        );

        $first['otp']->refresh();

        $this->assertSame(
            'INVALIDATED',
            $first['otp']->status
        );

        $this->assertSame(
            'ACTIVE',
            $second['otp']->status
        );
    }

    public function test_correct_code_verifies_successfully(): void
    {
        [, $contact] = $this->createPhoneContact();

        $service = app(OtpService::class);

        $result = $service->request(
            $contact,
            'VERIFY_PHONE',
            'WHATSAPP'
        );

        $verified = $service->verify(
            $result['otp'],
            $result['code']
        );

        $result['otp']->refresh();

        $this->assertTrue($verified);

        $this->assertSame(
            'VERIFIED',
            $result['otp']->status
        );

        $this->assertNotNull(
            $result['otp']->verified_at
        );
    }

    public function test_wrong_code_increments_attempts(): void
    {
        [, $contact] = $this->createPhoneContact();

        $service = app(OtpService::class);

        $result = $service->request(
            $contact,
            'VERIFY_PHONE',
            'WHATSAPP'
        );

        $verified = $service->verify(
            $result['otp'],
            '000000'
        );

        $result['otp']->refresh();

        $this->assertFalse($verified);

        $this->assertSame(
            1,
            $result['otp']->attempts
        );

        $this->assertSame(
            'ACTIVE',
            $result['otp']->status
        );
    }

    public function test_otp_fails_after_three_wrong_attempts(): void
    {
        [, $contact] = $this->createPhoneContact();

        $service = app(OtpService::class);

        $result = $service->request(
            $contact,
            'VERIFY_PHONE',
            'WHATSAPP'
        );

        $service->verify($result['otp'], '000000');
        $service->verify($result['otp'], '000000');
        $service->verify($result['otp'], '000000');

        $result['otp']->refresh();

        $this->assertSame(
            3,
            $result['otp']->attempts
        );

        $this->assertSame(
            'FAILED',
            $result['otp']->status
        );

        $this->assertFalse(
            $service->verify($result['otp'], $result['code'])
        );
    }

    public function test_expired_otp_cannot_be_verified(): void
    {
        [, $contact] = $this->createPhoneContact();

        $service = app(OtpService::class);

        $result = $service->request(
            $contact,
            'VERIFY_PHONE',
            'WHATSAPP'
        );

        OtpCode::query()
            ->whereKey($result['otp']->id)
            ->update([
                'expires_at' => now()->subSecond(),
            ]);

        $result['otp']->refresh();

        $verified = $service->verify(
            $result['otp'],
            $result['code']
        );

        $result['otp']->refresh();

        $this->assertFalse($verified);

        $this->assertSame(
            'EXPIRED',
            $result['otp']->status
        );
    }

    private function createPhoneContact(): array
    {
        $account = Account::query()->create([
            'status' => 'ACTIVE',
        ]);

        $contact = AccountContact::query()->create([
            'account_id' => $account->id,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => false,
        ]);

        return [$account, $contact];
    }
}
