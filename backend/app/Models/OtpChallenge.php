<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OtpChallenge extends Model
{
    use HasUuids;

    protected $table = 'otp_challenges';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'account_id',
        'contact_id',
        'purpose',
        'code_hash',
        'attempts',
        'resend_count',
        'expires_at',
        'last_sent_at',
        'consumed_at',
        'invalidated_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'consumed_at' => 'datetime',
        'invalidated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function contact()
    {
        return $this->belongsTo(AccountContact::class, 'contact_id', 'id');
    }
}
