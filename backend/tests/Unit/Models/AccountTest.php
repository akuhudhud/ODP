<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\AccountProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_has_one_account_profile(): void
    {
        $account = Account::query()->create([
            'status' => 'ACTIVE',
        ]);

        AccountProfile::query()->create([
            'account_id' => $account->id,
            'display_name' => 'Test User',
        ]);

        $account->refresh();

        $this->assertInstanceOf(
            AccountProfile::class,
            $account->accountProfile
        );

        $this->assertSame(
            $account->id,
            $account->accountProfile->account_id
        );
    }
}
