<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\AccountContact;
use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_code_belongs_to_account_and_contact(): void
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

        $otp = OtpCode::query()->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'purpose' => 'VERIFY_PHONE',
            'channel' => 'WHATSAPP',
            'code_hash' => password_hash('123456', PASSWORD_DEFAULT),
            'status' => 'ACTIVE',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
            'last_sent_at' => now(),
        ]);

        $otp->refresh();

        $this->assertInstanceOf(
            Account::class,
            $otp->account
        );

        $this->assertInstanceOf(
            AccountContact::class,
            $otp->contact
        );

        $this->assertSame(
            $account->id,
            $otp->account->id
        );

        $this->assertSame(
            $contact->id,
            $otp->contact->id
        );
    }
}
