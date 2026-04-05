<?php

namespace App\Services;

use App\Models\RecommendationConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * 推荐策略配置：DB 覆盖 + 文件默认，带缓存；保存后通过 Model 事件清缓存即时生效。
 */
final class RecommendationConfigService
{
    public const CACHE_KEY = 'recommendation_strategy_merged_v1';

    /**
     * @return array<string, mixed>
     */
    public function merged(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $defaults = config('recommendation_strategy_defaults', []);
            if (! is_array($defaults)) {
                $defaults = [];
            }
            $rows = RecommendationConfig::query()->get(['config_key', 'config_value']);
            foreach ($rows as $row) {
                $k = (string) $row->config_key;
                if ($k === '') {
                    continue;
                }
                $decoded = json_decode((string) $row->config_value, true);
                if (is_array($decoded)) {
                    $defaults[$k] = array_replace_recursive($defaults[$k] ?? [], $decoded);
                }
            }

            return $defaults;
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return data_get($this->merged(), $path, $default);
    }

    public function float(string $path): float
    {
        $v = $this->get($path);
        if (! is_numeric($v)) {
            throw new \RuntimeException("Recommendation config missing or invalid float: {$path}");
        }

        return (float) $v;
    }

    public function int(string $path): int
    {
        $v = $this->get($path);
        if (! is_numeric($v)) {
            throw new \RuntimeException("Recommendation config missing or invalid int: {$path}");
        }

        return (int) $v;
    }

    public function bool(string $path): bool
    {
        return (bool) $this->get($path, false);
    }

    /**
     * @return array<mixed>
     */
    public function array(string $path): array
    {
        $v = $this->get($path, []);
        if (! is_array($v)) {
            return [];
        }

        return $v;
    }

    public function switchEnabled(string $path): bool
    {
        return $this->bool('feature_switches.'.$path);
    }

    public function nextPivotKey(?string $lastPivot): string
    {
        $order = $this->array('reroll_strategy.pivot_order');
        $order = array_values(array_filter($order, fn ($x) => is_string($x) && $x !== ''));
        if ($order === []) {
            $order = ['warming', 'light', 'home_comfort', 'quick', 'high_protein'];
        }
        if ($lastPivot === null || $lastPivot === '') {
            return $order[0];
        }
        $idx = array_search($lastPivot, $order, true);

        return $order[($idx === false ? 0 : $idx + 1) % count($order)];
    }

    /**
     * @return array{key: string, label_cn: string, hint_cn: string}
     */
    public function pivotSpec(string $key): array
    {
        $specs = $this->array('reroll_strategy.pivot_specs');
        $row = is_array($specs[$key] ?? null) ? $specs[$key] : null;
        $label = is_array($row) ? trim((string) ($row['label_cn'] ?? '')) : '';
        $hint = is_array($row) ? trim((string) ($row['hint_cn'] ?? '')) : '';
        if ($label === '') {
            $label = $key;
        }
        if ($hint === '') {
            $hint = $label;
        }

        return ['key' => $key, 'label_cn' => $label, 'hint_cn' => $hint];
    }

    /**
     * @param  array<string, mixed>  $sections  config_key => array payload
     */
    public function saveSections(array $sections): void
    {
        $defaults = config('recommendation_strategy_defaults', []);
        if (! is_array($defaults)) {
            $defaults = [];
        }
        foreach ($sections as $configKey => $payload) {
            if (! is_string($configKey) || $configKey === '' || ! is_array($payload)) {
                continue;
            }
            $base = is_array($defaults[$configKey] ?? null) ? $defaults[$configKey] : [];
            $merged = array_replace_recursive($base, $payload);
            RecommendationConfig::query()->updateOrCreate(
                ['config_key' => $configKey],
                [
                    'config_value' => json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'config_type' => 'json',
                ],
            );
        }
        $this->forgetCache();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sectionsForAdminForm(): array
    {
        $merged = $this->merged();
        $keys = array_keys(config('recommendation_strategy_defaults', []));
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = is_array($merged[$k] ?? null) ? $merged[$k] : [];
        }

        return $out;
    }

    /**
     * 扁平化为一维 dot key => scalar（表单用）。
     *
     * @return array<string, int|float|string|bool>
     */
    public function flattenForForm(): array
    {
        $flat = [];
        foreach ($this->sectionsForAdminForm() as $section => $data) {
            foreach (Arr::dot([$section => $data]) as $path => $value) {
                if (is_array($value)) {
                    continue;
                }
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    /**
     * @param  array<string, mixed>  $flat  dot paths from form
     * @return array<string, array<string, mixed>>
     */
    public function unflattenFromForm(array $flat): array
    {
        $nested = [];
        foreach ($flat as $path => $value) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            data_set($nested, $path, $value);
        }
        $out = [];
        foreach (array_keys(config('recommendation_strategy_defaults', [])) as $k) {
            if (isset($nested[$k]) && is_array($nested[$k])) {
                $out[$k] = $nested[$k];
            }
        }

        return $out;
    }
}
