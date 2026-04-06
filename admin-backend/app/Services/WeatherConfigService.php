<?php

namespace App\Services;

use App\Models\BusinessConfig;
use Illuminate\Support\Arr;

final class WeatherConfigService
{
    public const CONFIG_KEY = 'miniapp_weather';

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $row = BusinessConfig::query()->where('config_key', self::CONFIG_KEY)->first();
        $value = is_array($row?->config_value) ? $row->config_value : [];
        $defaults = $this->defaultConfig();

        return array_merge($defaults, Arr::only($value, array_keys($defaults)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveConfig(array $payload): BusinessConfig
    {
        $current = $this->getConfig();
        $merged = array_merge($current, Arr::only($payload, array_keys($this->defaultConfig())));
        // 空白密钥不覆盖已配置值
        if (trim((string) ($payload['api_key'] ?? '')) === '' && trim((string) ($current['api_key'] ?? '')) !== '') {
            $merged['api_key'] = $current['api_key'];
        }

        $row = BusinessConfig::query()->firstOrNew(['config_key' => self::CONFIG_KEY]);
        $row->config_name = '小程序天气接口';
        $row->config_group = 'weather';
        $row->is_enabled = (bool) ($payload['enabled'] ?? false);
        $row->sort = (int) ($payload['sort'] ?? 20);
        $row->remark = (string) ($payload['remark'] ?? '首页城市与天气展示配置');
        $row->config_value = Arr::only($merged, array_keys($this->defaultConfig()));
        $row->save();

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultConfig(): array
    {
        return [
            'enabled' => false,
            'provider' => 'qweather',
            'api_key' => '',
            'geo_base_url' => 'https://geoapi.qweather.com',
            'weather_base_url' => 'https://devapi.qweather.com',
            'default_city' => '深圳',
            'default_location_id' => '',
            'request_timeout_sec' => 3,
        ];
    }
}

