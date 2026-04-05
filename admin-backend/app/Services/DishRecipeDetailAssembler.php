<?php

namespace App\Services;

use App\Models\DishRecipe;
use App\Models\RecommendationRecord;
use App\Models\RecommendationSession;

/**
 * 合并后台结构化菜谱与推荐记录快照，供做法详情页展示。
 */
final class DishRecipeDetailAssembler
{
    /**
     * @return array{
     *   source: string,
     *   title: string,
     *   description: string|null,
     *   ingredients: list<string>,
     *   seasonings: list<string>,
     *   steps: list<array{step_no: int, content: string}>,
     *   cooking_time: string|null,
     *   difficulty: string|null,
     *   tips: list<string>,
     *   legacy_recipe_content: string|null,
     *   is_actionable: bool,
     *   display_tags: list<string>,
     *   cuisine: string|null
     * }
     */
    public function forRecord(RecommendationRecord $record): array
    {
        $dishName = trim((string) $record->recommended_dish);
        $key = RecommendationSession::dishKey($dishName);

        $admin = DishRecipe::query()
            ->where('dish_key', $key)
            ->where('is_active', true)
            ->first();

        $snapIngredients = $this->stringList($record->ingredients ?? []);
        $legacy = trim((string) $record->recipe_content);
        $legacy = $legacy !== '' ? $legacy : null;

        $ingredients = $this->stringList($admin?->ingredients ?? []);
        if ($ingredients === []) {
            $ingredients = $snapIngredients;
        }

        $seasonings = $this->stringList($admin?->seasonings ?? []);
        $tips = $this->stringList($admin?->tips ?? []);
        $steps = $this->normalizeSteps($admin?->steps ?? null);

        $title = $admin !== null && trim((string) $admin->title) !== ''
            ? trim((string) $admin->title)
            : $dishName;

        $description = $admin !== null ? $this->nullableTrim($admin->description) : null;

        $source = $admin !== null ? 'admin' : 'snapshot';

        $isActionable = $steps !== []
            || $legacy !== null
            || $ingredients !== []
            || $seasonings !== [];

        return [
            'source' => $source,
            'title' => $title,
            'description' => $description,
            'ingredients' => $ingredients,
            'seasonings' => $seasonings,
            'steps' => $steps,
            'cooking_time' => $admin !== null ? $this->nullableTrim($admin->cooking_time) : null,
            'difficulty' => $admin !== null ? $this->nullableTrim($admin->difficulty) : null,
            'tips' => $tips,
            'legacy_recipe_content' => $legacy,
            'is_actionable' => $isActionable,
            'display_tags' => $this->stringList($record->tags ?? []),
            'cuisine' => $record->cuisine !== null && trim((string) $record->cuisine) !== ''
                ? trim((string) $record->cuisine)
                : null,
        ];
    }

    /**
     * 仅后台菜谱（做法详情页按 dish_recipe id 打开时使用）。
     *
     * @return array{
     *   source: string,
     *   title: string,
     *   description: string|null,
     *   ingredients: list<string>,
     *   seasonings: list<string>,
     *   steps: list<array{step_no: int, content: string}>,
     *   cooking_time: string|null,
     *   difficulty: string|null,
     *   tips: list<string>,
     *   legacy_recipe_content: string|null,
     *   is_actionable: bool,
     *   display_tags: list<string>,
     *   cuisine: string|null
     * }
     */
    public function standalone(DishRecipe $dish): array
    {
        $ingredients = $this->stringList($dish->ingredients ?? []);
        $seasonings = $this->stringList($dish->seasonings ?? []);
        $tips = $this->stringList($dish->tips ?? []);
        $steps = $this->normalizeSteps($dish->steps ?? null);

        $isActionable = $steps !== [] || $ingredients !== [] || $seasonings !== [];

        return [
            'source' => 'admin',
            'title' => trim((string) $dish->title),
            'description' => $this->nullableTrim($dish->description),
            'ingredients' => $ingredients,
            'seasonings' => $seasonings,
            'steps' => $steps,
            'cooking_time' => $this->nullableTrim($dish->cooking_time),
            'difficulty' => $this->nullableTrim($dish->difficulty),
            'tips' => $tips,
            'legacy_recipe_content' => null,
            'is_actionable' => $isActionable,
            'display_tags' => [],
            'cuisine' => null,
        ];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $x) {
            if (is_string($x) && trim($x) !== '') {
                $out[] = trim($x);
            }
        }

        return $out;
    }

    private function nullableTrim(?string $s): ?string
    {
        if ($s === null) {
            return null;
        }
        $t = trim($s);

        return $t !== '' ? $t : null;
    }

    /**
     * @return list<array{step_no: int, content: string}>
     */
    private function normalizeSteps(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $content = isset($row['content']) ? trim((string) $row['content']) : '';
            if ($content === '') {
                continue;
            }
            $no = isset($row['step_no']) && is_numeric($row['step_no']) ? (int) $row['step_no'] : count($out) + 1;
            $out[] = ['step_no' => $no, 'content' => $content];
        }
        usort($out, static fn (array $a, array $b): int => $a['step_no'] <=> $b['step_no']);
        $i = 1;
        foreach ($out as &$row) {
            $row['step_no'] = $i++;
        }
        unset($row);

        return $out;
    }
}
