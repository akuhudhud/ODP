<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\AccountContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_has_many_account_contacts(): void
    {
        $account = Account::query()->create([
            'status' => 'ACTIVE',
        ]);

        AccountContact::query()->create([
            'account_id' => $account->id,
            'type' => 'PHONE',
            'value' => '+60123456789',
            'status' => 'ACTIVE',
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        AccountContact::query()->create([
            'account_id' => $account->id,
            'type' => 'EMAIL',
            'value' => 'test@example.com',
            'status' => 'ACTIVE',
            'is_verified' => false,
        ]);

        $account->refresh();

        $this->assertCount(2, $account->accountContacts);

        $this->assertTrue(
            $account->accountContacts
                ->contains(fn (AccountContact $contact) => $contact->type === 'PHONE')
        );

        $this->assertTrue(
            $account->accountContacts
                ->contains(fn (AccountContact $contact) => $contact->type === 'EMAIL')
        );
    }
}
