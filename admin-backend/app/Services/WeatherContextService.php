<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * 天气拉取与标准化标签（系统侧完成，模型只消费 JSON）。
 */
final class WeatherContextService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Carbon $queriedForDate): array
    {
        $cfg = config('recommendation.weather', []);
        if (empty($cfg['enabled'])) {
            return $this->empty('天气能力未启用');
        }

        $city = (string) ($cfg['city'] ?? 'Beijing');
        $timeout = max(1, (int) ($cfg['timeout'] ?? 3));
        $url = 'https://wttr.in/'.rawurlencode($city).'?format=j1';

        try {
            $resp = Http::withHeaders(['User-Agent' => 'WhatToEatBot/1.0 (recommendation-context)'])
                ->timeout($timeout)
                ->get($url);

            if (! $resp->successful()) {
                return $this->empty('天气上游 HTTP '.$resp->status());
            }

            $json = $resp->json();
            if (! is_array($json)) {
                return $this->empty('天气解析失败');
            }

            $current = $json['current_condition'][0] ?? null;
            if (! is_array($current)) {
                return $this->empty('无 current_condition');
            }

            $code = isset($current['weatherCode']) ? (string) $current['weatherCode'] : '';
            $tempC = isset($current['temp_C']) ? (float) $current['temp_C'] : null;
            $feels = isset($current['FeelsLikeC']) ? (float) $current['FeelsLikeC'] : (isset($current['feels_like_c']) ? (float) $current['feels_like_c'] : $tempC);
            $humidity = isset($current['humidity']) ? (float) $current['humidity'] : null;
            $windKmh = null;
            if (isset($current['windspeedKmph'])) {
                $windKmh = (float) $current['windspeedKmph'];
            } elseif (isset($current['WindGustKmph'])) {
                $windKmh = (float) $current['WindGustKmph'];
            }

            $desc = '';
            if (isset($current['lang_zh']) && is_array($current['lang_zh']) && isset($current['lang_zh'][0]['value'])) {
                $desc = (string) $current['lang_zh'][0]['value'];
            } elseif (isset($current['weatherDesc'][0]['value'])) {
                $desc = (string) $current['weatherDesc'][0]['value'];
            } elseif (isset($current['weatherDesc']) && is_string($current['weatherDesc'])) {
                $desc = $current['weatherDesc'];
            }

            $precipCodes = array_merge(
                range(176, 263),
                range(299, 310),
                [353, 356, 359, 386, 389, 392, 395]
            );
            $codeNum = is_numeric($code) ? (int) $code : -1;
            $isPrecip = $codeNum >= 200 || in_array($codeNum, $precipCodes, true) || str_contains($desc, '雨') || str_contains($desc, '雪');

            $weatherType = $this->normalizeWeatherType($codeNum, $desc, $isPrecip);
            $weatherTags = $this->buildWeatherTags($weatherType, $tempC, $feels, $isPrecip, $desc);

            return [
                'available' => true,
                'city' => $city,
                'queried_at' => $queriedForDate->toIso8601String(),
                'weather_type' => $weatherType,
                'temperature' => $tempC,
                'feels_like' => $feels,
                'humidity' => $humidity,
                'wind_level' => $this->windLevelFromKmh($windKmh),
                'wind_speed_kmph' => $windKmh,
                'weather_tags' => $weatherTags,
                'weather_code' => $code,
                'description' => $desc,
                'is_precipitation' => $isPrecip,
                'temp_c' => $tempC,
                'raw_source' => 'wttr.in',
            ];
        } catch (\Throwable $e) {
            return $this->empty('天气请求异常：'.mb_substr($e->getMessage(), 0, 120));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function empty(string $note): array
    {
        return [
            'available' => false,
            'note' => $note,
            'weather_type' => null,
            'temperature' => null,
            'feels_like' => null,
            'humidity' => null,
            'wind_level' => null,
            'weather_tags' => [],
            'weather_code' => null,
            'description' => null,
            'is_precipitation' => false,
            'temp_c' => null,
        ];
    }

    private function normalizeWeatherType(int $codeNum, string $desc, bool $isPrecip): string
    {
        $d = mb_strtolower($desc);
        if (str_contains($d, '雪') || ($codeNum >= 320 && $codeNum <= 338) || in_array($codeNum, [179, 227, 323, 326, 329, 332, 335, 338, 368, 371, 395], true)) {
            return 'snow';
        }
        if ($isPrecip) {
            return str_contains($d, '雷') || str_contains($d, '暴') ? 'storm' : 'rainy';
        }
        if (str_contains($d, '雾') || str_contains($d, '霾')) {
            return 'foggy';
        }
        if (str_contains($d, '阴') || str_contains($d, '多云') || in_array($codeNum, [116, 119, 122], true)) {
            return 'cloudy';
        }
        if (str_contains($d, '晴') || in_array($codeNum, [113], true)) {
            return 'sunny';
        }

        return 'unknown';
    }

    /**
     * @return list<string>
     */
    private function buildWeatherTags(string $weatherType, ?float $temp, ?float $feels, bool $isPrecip, string $desc): array
    {
        $tags = [];
        if ($weatherType === 'rainy' || $weatherType === 'storm') {
            $tags[] = '雨天';
        }
        if ($weatherType === 'snow') {
            $tags[] = '雪天';
        }
        if ($weatherType === 'sunny') {
            $tags[] = '晴天';
        }
        if ($weatherType === 'cloudy') {
            $tags[] = '阴天';
        }
        if ($weatherType === 'foggy') {
            $tags[] = '雾霾';
        }
        $t = $feels ?? $temp;
        if (is_numeric($t)) {
            if ((float) $t >= 30) {
                $tags[] = '高温';
            }
            if ((float) $t < 10) {
                $tags[] = '低温';
            }
            if ((float) $t < 5) {
                $tags[] = '寒冷';
            }
        }
        if ($isPrecip && is_numeric($t) && (float) $t < 14) {
            $tags[] = '湿冷';
        }

        return array_values(array_unique($tags));
    }

    private function windLevelFromKmh(?float $kmh): ?string
    {
        if ($kmh === null) {
            return null;
        }
        if ($kmh < 10) {
            return 'calm';
        }
        if ($kmh < 25) {
            return 'light';
        }
        if ($kmh < 40) {
            return 'moderate';
        }
        if ($kmh < 60) {
            return 'strong';
        }

        return 'gale';
    }
}
