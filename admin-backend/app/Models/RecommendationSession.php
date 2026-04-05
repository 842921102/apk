<?php

namespace App\Models;

use App\Services\RecommendationConfigService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property Carbon $status_date
 * @property list<string>|null $excluded_dishes
 * @property string|null $last_pivot
 * @property string|null $last_recommended_dish
 */
#[Fillable([
    'id',
    'user_id',
    'status_date',
    'excluded_dishes',
    'last_pivot',
    'last_recommended_dish',
])]
class RecommendationSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'status_date' => 'date',
            'excluded_dishes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  list<string>  $dishes
     * @return list<string>
     */
    public static function normalizeExcluded(array $dishes): array
    {
        $out = [];
        foreach ($dishes as $d) {
            $s = trim(is_string($d) ? $d : '');
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }

    public static function dishKey(string $dish): string
    {
        return mb_strtolower(trim($dish));
    }

    /**
     * @param  list<string>  $excluded
     */
    public static function isExcluded(string $dish, array $excluded): bool
    {
        $k = self::dishKey($dish);
        foreach ($excluded as $e) {
            if (self::dishKey($e) === $k) {
                return true;
            }
        }

        return false;
    }

    public function appendExcludedDish(string $dish): void
    {
        $list = $this->excluded_dishes ?? [];
        $list[] = $dish;
        $this->update([
            'excluded_dishes' => self::normalizeExcluded($list),
        ]);
    }

    public function setLastPivot(string $pivot): void
    {
        $this->update(['last_pivot' => $pivot]);
    }

    public function setLastRecommendedDish(string $dish): void
    {
        $this->update(['last_recommended_dish' => Str::limit(trim($dish), 120, '')]);
    }

    /**
     * @deprecated 使用 RecommendationConfigService::nextPivotKey / pivotSpec（配置化）
     */
    public static function nextPivotKey(?string $lastPivot): string
    {
        return app(RecommendationConfigService::class)->nextPivotKey($lastPivot);
    }

    /**
     * @deprecated 使用 RecommendationConfigService::pivotSpec
     *
     * @return array{key: string, label_cn: string, hint_cn: string}
     */
    public static function pivotSpec(string $key): array
    {
        return app(RecommendationConfigService::class)->pivotSpec($key);
    }
}
