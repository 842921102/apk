<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'provider_id',
    'model_code',
    'model_name',
    'model_type',
    'api_path',
    'is_enabled',
    'is_default',
    'supports_temperature',
    'supports_timeout',
    'description',
])]
class AiModel extends Model
{
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'supports_temperature' => 'boolean',
        'supports_timeout' => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    public function sceneConfigs(): HasMany
    {
        return $this->hasMany(AiModelConfig::class, 'model_id');
    }
}

