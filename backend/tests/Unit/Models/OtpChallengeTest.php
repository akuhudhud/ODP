<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\AccountContact;
use App\Models\OtpChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class OtpChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_challenge_can_be_created_and_casts_dates(): void
    {
        $account = Account::create([
            'status' => 'ACTIVE',
        ]);

        $contact = AccountContact::create([
            'account_id' => $account->id,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => true,
            'verified_at' => Carbon::now(),
        ]);

        $expiresAt = Carbon::now()->addMinutes(5);
        $lastSentAt = Carbon::now();
        $createdAt = Carbon::now();

        $challenge = OtpChallenge::create([
            'id' => (string) Str::uuid7(),
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'purpose' => 'VERIFY_PHONE',
            'code_hash' => hash('sha256', '123456'),
            'attempts' => 0,
            'resend_count' => 0,
            'expires_at' => $expiresAt,
            'last_sent_at' => $lastSentAt,
            'created_at' => $createdAt,
        ]);

        $this->assertDatabaseHas('otp_challenges', [
            'id' => $challenge->id,
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'purpose' => 'VERIFY_PHONE',
            'attempts' => 0,
            'resend_count' => 0,
        ]);

        $this->assertInstanceOf(Carbon::class, $challenge->expires_at);
        $this->assertInstanceOf(Carbon::class, $challenge->last_sent_at);
        $this->assertInstanceOf(Carbon::class, $challenge->created_at);
    }

    public function test_otp_challenge_belongs_to_account_and_contact(): void
    {
        $account = Account::create([
            'status' => 'ACTIVE',
        ]);

        $contact = AccountContact::create([
            'account_id' => $account->id,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => true,
            'verified_at' => Carbon::now(),
        ]);

        $challenge = OtpChallenge::create([
            'id' => (string) Str::uuid7(),
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'purpose' => 'VERIFY_PHONE',
            'code_hash' => hash('sha256', '123456'),
            'attempts' => 0,
            'resend_count' => 0,
            'expires_at' => Carbon::now()->addMinutes(5),
            'last_sent_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);

        $this->assertTrue($challenge->account->is($account));
        $this->assertTrue($challenge->contact->is($contact));
    }
}
