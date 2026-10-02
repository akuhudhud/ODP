<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Account extends Model
{
    use HasUuids;

    protected $table = 'accounts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'status',
        'created_at',
        'updated_at',
        'deactivated_at',
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
