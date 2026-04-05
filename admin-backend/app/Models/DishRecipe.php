<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $dish_key
 * @property string $title
 * @property string|null $description
 * @property list<string>|null $ingredients
 * @property list<string>|null $seasonings
 * @property list<array{step_no?: int, content?: string}>|null $steps
 * @property string|null $cooking_time
 * @property string|null $difficulty
 * @property list<string>|null $tips
 * @property bool $is_active
 */
#[Fillable([
    'dish_key',
    'title',
    'description',
    'ingredients',
    'seasonings',
    'steps',
    'cooking_time',
    'difficulty',
    'tips',
    'is_active',
])]
class DishRecipe extends Model
{
    public static function activeIdForRecommendedDish(string $recommendedDish): ?int
    {
        $key = RecommendationSession::dishKey(trim($recommendedDish));
        if ($key === '') {
            return null;
        }
        $id = self::query()->where('dish_key', $key)->where('is_active', true)->value('id');

        return is_numeric($id) ? (int) $id : null;
    }

    protected function casts(): array
    {
        return [
            'ingredients' => 'array',
            'seasonings' => 'array',
            'steps' => 'array',
            'tips' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DishRecipe $m): void {
            $title = trim((string) $m->title);
            if ($title !== '' && trim((string) $m->dish_key) === '') {
                $m->dish_key = RecommendationSession::dishKey($title);
            }
            $steps = $m->steps;
            if (is_array($steps) && $steps !== []) {
                $out = [];
                $i = 1;
                foreach ($steps as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $content = isset($row['content']) ? trim((string) $row['content']) : '';
                    if ($content === '') {
                        continue;
                    }
                    $out[] = [
                        'step_no' => $i,
                        'content' => $content,
                    ];
                    $i++;
                }
                $m->steps = $out;
            }
        });
    }
}
