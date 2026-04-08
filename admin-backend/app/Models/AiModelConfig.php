<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'scene_code',
    'provider_id',
    'model_id',
    'api_key',
    'base_url_override',
    'temperature',
    'timeout_ms',
    'fallback_model_codes',
    'is_enabled',
    'is_default',
    'remark',
    'created_by',
    'updated_by',
])]
#[Hidden([
    'api_key',
])]
class AiModelConfig extends Model
{
    protected $casts = [
        'api_key' => 'encrypted',
        'temperature' => 'decimal:2',
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'timeout_ms' => 'integer',
    ];

    protected $appends = [
        'key_masked',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getKeyMaskedAttribute(): string
    {
        $raw = (string) $this->getAttribute('api_key');
        if ($raw === '') {
            return '';
        }
        if (mb_strlen($raw) <= 8) {
            return '****';
        }

        return mb_substr($raw, 0, 4).'****'.mb_substr($raw, -4);
    }
}

