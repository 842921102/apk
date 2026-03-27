<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'scene_code',
    'provider_id',
    'model_id',
    'request_url',
    'request_payload',
    'response_payload',
    'status',
    'error_message',
    'tested_by',
])]
class AiConnectionTestLog extends Model
{
    public const UPDATED_AT = null;

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by');
    }
}

