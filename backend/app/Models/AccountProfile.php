<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountProfile extends Model
{
    protected $table = 'account_profiles';

    protected $primaryKey = 'account_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'account_id',
        'display_name',
        'display_name_changed_at',
        'profile_photo',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'display_name_changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
