<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'provider_code',
    'provider_name',
    'provider_type',
    'base_url',
    'is_enabled',
    'description',
])]
class AiProvider extends Model
{
    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class, 'provider_id');
    }

    public function sceneConfigs(): HasMany
    {
        return $this->hasMany(AiModelConfig::class, 'provider_id');
    }
}

