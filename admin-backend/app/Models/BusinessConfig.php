<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'config_key',
    'config_name',
    'config_group',
    'config_value',
    'is_enabled',
    'sort',
    'remark',
])]
class BusinessConfig extends Model
{
    protected $casts = [
        'config_value' => 'array',
        'is_enabled' => 'boolean',
        'sort' => 'integer',
    ];
}
