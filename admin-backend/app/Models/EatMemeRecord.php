<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'channel',
    'taste',
    'avoid',
    'people',
    'result_title',
    'result_cuisine',
    'result_ingredients',
    'result_content',
    'status',
    'error_message',
    'source_ip',
    'user_agent',
    'requested_at',
])]
class EatMemeRecord extends Model
{
    protected $casts = [
        'result_ingredients' => 'array',
        'requested_at' => 'datetime',
        'people' => 'integer',
    ];
}

