<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'feature_type',
    'channel',
    'user_id',
    'status',
    'title',
    'sub_type',
    'input_payload',
    'result_payload',
    'result_summary',
    'error_message',
    'source_ip',
    'user_agent',
    'requested_at',
])]
class FeatureDataRecord extends Model
{
    protected $casts = [
        'input_payload' => 'array',
        'result_payload' => 'array',
        'requested_at' => 'datetime',
        'user_id' => 'integer',
    ];
}

