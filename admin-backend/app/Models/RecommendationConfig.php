<?php

namespace App\Models;

use App\Services\RecommendationConfigService;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $config_key
 * @property string $config_value
 * @property string $config_type
 * @property string|null $description
 */
class RecommendationConfig extends Model
{
    protected $fillable = [
        'config_key',
        'config_value',
        'config_type',
        'description',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            if (app()->bound(RecommendationConfigService::class)) {
                app(RecommendationConfigService::class)->forgetCache();
            }
        });

        static::deleted(function (): void {
            if (app()->bound(RecommendationConfigService::class)) {
                app(RecommendationConfigService::class)->forgetCache();
            }
        });
    }
}
