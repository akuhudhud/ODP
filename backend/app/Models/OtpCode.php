<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OtpCode extends Model
{
    use HasUuids;

    protected $table = 'otp_codes';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'account_id',
        'contact_id',
        'purpose',
        'channel',
        'code_hash',
        'status',
        'attempts',
        'expires_at',
        'last_sent_at',
        'verified_at',
        'invalidated_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'verified_at' => 'datetime',
        'invalidated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
