<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class MiniappWeatherAmbientService
{
    public function __construct(private readonly WeatherConfigService $configService)
    {
    }

    /**
     * @return array{city_name:string,weather_text:string,weather_code:string,weather_icon_emoji:string,temperature?:string|null}
     */
    public function resolve(?float $latitude = null, ?float $longitude = null): array
    {
        return $this->resolveWithDebug($latitude, $longitude)['ambient'];
    }

    /**
     * @return array{
     *   ambient: array{city_name:string,weather_text:string,weather_code:string,weather_icon_emoji:string,temperature?:string|null},
     *   debug: array<string, mixed>
     * }
     */
    public function resolveWithDebug(?float $latitude = null, ?float $longitude = null): array
    {
        $cfg = $this->configService->getConfig();
        $fallback = $this->fallback((string) ($cfg['default_city'] ?? '深圳'));
        if (! (bool) ($cfg['enabled'] ?? false)) {
            return [
                'ambient' => $fallback,
                'debug' => ['ok' => false, 'fallback' => true, 'reason' => 'weather_disabled'],
            ];
        }

        $provider = (string) ($cfg['provider'] ?? 'qweather');
        if (! in_array($provider, ['qweather', 'amap'], true)) {
            return [
                'ambient' => $fallback,
                'debug' => ['ok' => false, 'fallback' => true, 'reason' => 'unsupported_provider'],
            ];
        }

        $apiKey = trim((string) ($cfg['api_key'] ?? ''));
        if ($apiKey === '') {
            return [
                'ambient' => $fallback,
                'debug' => ['ok' => false, 'fallback' => true, 'reason' => 'missing_api_key'],
            ];
        }

        $timeout = max(1, (int) ($cfg['request_timeout_sec'] ?? 3));
        if ($provider === 'amap') {
            return $this->resolveViaAmap($cfg, $fallback, $apiKey, $timeout, $latitude, $longitude);
        }

        return $this->resolveViaQWeather($cfg, $fallback, $apiKey, $timeout, $latitude, $longitude);
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @param  array{city_name:string,weather_text:string,weather_code:string,weather_icon_emoji:string}  $fallback
     * @return array{ambient: array{city_name:string,weather_text:string,weather_code:string,weather_icon_emoji:string,temperature?:string}, debug: array<string, mixed>}
     */
    private function resolveViaQWeather(array $cfg, array $fallback, string $apiKey, int $timeout, ?float $latitude, ?float $longitude): array
    {
        $geoBase = rtrim((string) ($cfg['geo_base_url'] ?? 'https://geoapi.qweather.com'), '/');
        $weatherBase = rtrim((string) ($cfg['weather_base_url'] ?? 'https://devapi.qweather.com'), '/');
        $city = (string) ($cfg['default_city'] ?? '深圳');
        $locationId = trim((string) ($cfg['default_location_id'] ?? ''));
        $debug = [
            'ok' => false,
            'fallback' => true,
            'provider' => 'qweather',
            'geo_lookup_http' => null,
            'weather_http' => null,
            'reason' => 'unknown',
        ];

        try {
            if ($latitude !== null && $longitude !== null) {
                $lookup = Http::timeout($timeout)->get($geoBase.'/v2/city/lookup', [
                    'location' => $longitude.','.$latitude,
                    'key' => $apiKey,
                ]);
                $debug['geo_lookup_http'] = $lookup->status();
                if ($lookup->successful()) {
                    $json = $lookup->json();
                    $loc = is_array($json['location'] ?? null) ? ($json['location'][0] ?? null) : null;
                    if (is_array($loc)) {
                        $city = (string) ($loc['name'] ?? $city);
                        $locationId = (string) ($loc['id'] ?? $locationId);
                    }
                } else {
                    $debug['reason'] = 'geo_lookup_failed';
                }
            }

            if ($locationId === '' && $city !== '') {
                $lookup = Http::timeout($timeout)->get($geoBase.'/v2/city/lookup', [
                    'location' => $city,
                    'key' => $apiKey,
                ]);
                $debug['geo_lookup_http'] = $lookup->status();
                if ($lookup->successful()) {
                    $json = $lookup->json();
                    $loc = is_array($json['location'] ?? null) ? ($json['location'][0] ?? null) : null;
                    if (is_array($loc)) {
                        $city = (string) ($loc['name'] ?? $city);
                        $locationId = (string) ($loc['id'] ?? $locationId);
                    }
                } else {
                    $debug['reason'] = 'city_lookup_failed';
                }
            }

            $weatherLocation = $locationId !== '' ? $locationId : $city;
            if ($weatherLocation === '') {
                return ['ambient' => $fallback, 'debug' => array_merge($debug, ['reason' => 'empty_weather_location'])];
            }
            $weatherResp = Http::timeout($timeout)->get($weatherBase.'/v7/weather/now', [
                'location' => $weatherLocation,
                'key' => $apiKey,
                'lang' => 'zh',
            ]);
            $debug['weather_http'] = $weatherResp->status();
            if (! $weatherResp->successful()) {
                return ['ambient' => $fallback, 'debug' => array_merge($debug, ['reason' => 'weather_request_failed'])];
            }
            $json = $weatherResp->json();
            $now = is_array($json['now'] ?? null) ? $json['now'] : null;
            if (! is_array($now)) {
                return ['ambient' => $fallback, 'debug' => array_merge($debug, ['reason' => 'weather_payload_invalid'])];
            }
            $code = (string) ($now['icon'] ?? '');
            $text = (string) ($now['text'] ?? '晴');
            $temp = $this->formatAmbientTemperature($now['temp'] ?? null);

            return [
                'ambient' => array_filter([
                    'city_name' => $city ?: (string) ($cfg['default_city'] ?? '深圳'),
                    'weather_text' => $text ?: '晴',
                    'weather_code' => $this->normalizeWeatherCode($code, $text),
                    'weather_icon_emoji' => $this->emojiByQWeatherCode($code),
                    'temperature' => $temp,
                ], static fn ($v) => $v !== null && $v !== ''),
                'debug' => array_merge($debug, ['ok' => true, 'fallback' => false, 'reason' => 'ok']),
            ];
        } catch (\Throwable $e) {
            return [
                'ambient' => $fallback,
                'debug' => array_merge($debug, ['reason' => 'exception', 'error' => mb_substr($e->getMessage(), 0, 160)]),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @param  array{city_name:string,weather_text:string,weather_code:string,weather_icon_emoji:string}  $fallback
     * @return array{ambient: array{city_name:string,weather_text:string,weather_code:string,weather_icon_emoji:string,temperature?:string}, debug: array<string, mixed>}
     */
    private function resolveViaAmap(array $cfg, array $fallback, string $apiKey, int $timeout, ?float $latitude, ?float $longitude): array
    {
        $geoBase = rtrim((string) ($cfg['geo_base_url'] ?? 'https://restapi.amap.com'), '/');
        $weatherBase = rtrim((string) ($cfg['weather_base_url'] ?? 'https://restapi.amap.com'), '/');
        $city = (string) ($cfg['default_city'] ?? '深圳');
        $adcode = trim((string) ($cfg['default_location_id'] ?? ''));
        $debug = [
            'ok' => false,
            'fallback' => true,
            'provider' => 'amap',
            'geo_lookup_http' => null,
            'weather_http' => null,
            'amap_status' => null,
            'amap_info' => null,
            'amap_infocode' => null,
            'reason' => 'unknown',
        ];

        try {
            if ($latitude !== null && $longitude !== null) {
                $regeo = Http::timeout($timeout)->get($geoBase.'/v3/geocode/regeo', [
                    'location' => $longitude.','.$latitude,
                    'key' => $apiKey,
                    'extensions' => 'base',
                ]);
                $debug['geo_lookup_http'] = $regeo->status();
                if ($regeo->successful()) {
                    $json = $regeo->json();
                    $debug['amap_status'] = (string) ($json['status'] ?? '');
                    $debug['amap_info'] = (string) ($json['info'] ?? '');
                    $debug['amap_infocode'] = (string) ($json['infocode'] ?? '');
                    if (($json['status'] ?? null) !== '1') {
                        return ['ambient' => $fallback, 'debug' => array_merge($debug, ['reason' => 'amap_geo_status_not_ok'])];
                    }
                    $component = $json['regeocode']['addressComponent'] ?? null;
                    if (is_array($component)) {
                        $cityRaw = $component['city'] ?? '';
                        if (is_array($cityRaw)) {
                            $cityRaw = $cityRaw[0] ?? '';
                        }
                        $cityName = trim((string) $cityRaw);
                        $province = trim((string) ($component['province'] ?? ''));
                        $district = trim((string) ($component['district'] ?? ''));
                        $city = $cityName !== '' ? $cityName : ($province !== '' ? $province : $city);
                        $adcode = trim((string) ($component['adcode'] ?? $adcode));
                        if ($city !== '' && $district !== '' && ! str_contains($city, $district)) {
                            $city = $city.$district;
                        }
                    }
                } else {
                    $debug['reason'] = 'geo_lookup_failed';
                }
            }

            if ($adcode === '' && $city !== '') {
                $geo = Http::timeout($timeout)->get($geoBase.'/v3/geocode/geo', [
                    'address' => $city,
                    'key' => $apiKey,
                ]);
                $debug['geo_lookup_http'] = $geo->status();
                if ($geo->successful()) {
                    $json = $geo->json();
                    $debug['amap_status'] = (string) ($json['status'] ?? '');
                    $debug['amap_info'] = (string) ($json['info'] ?? '');
                    $debug['amap_infocode'] = (string) ($json['infocode'] ?? '');
                    if (($json['status'] ?? null) !== '1') {
                        return ['ambient' => $fallback, 'debug' => array_merge($debug, ['reason' => 'amap_city_status_not_ok'])];
                    }
                    $item = is_array($json['geocodes'] ?? null) ? ($json['geocodes'][0] ?? null) : null;
                    if (is_array($item)) {
                        $adcode = trim((string) ($item['adcode'] ?? $adcode));
                        $city = trim((string) ($item['city'] ?? $city)) ?: $city;
                    }
                } else {
                    $debug['reason'] = 'city_lookup_failed';
                }
            }

            $weatherCity = $adcode !== '' ? $adcode : $city;
            if ($weatherCity === '') {
                return ['ambient' => $fallback, 'debug' => array_merge($debug, ['reason' => 'empty_weather_location'])];
            }
            $weatherResp = Http::timeout($timeout)->get($weatherBase.'/v3/weather/weatherInfo', [
                'city' => $weatherCity,
                'key' => $apiKey,
                'extensions' => 'base',
            ]);
            $debug['weather_http'] = $weatherResp->status();
            if (! $weatherResp->successful()) {
                return ['ambient' => $fallback, 'debug' => array_merge($debug, ['reason' => 'weather_request_failed'])];
            }
            $json = $weatherResp->json();
            $debug['amap_status'] = (string) ($json['status'] ?? '');
            $debug['amap_info'] = (string) ($json['info'] ?? '');
            $debug['amap_infocode'] = (string) ($json['infocode'] ?? '');
            if (($json['status'] ?? null) !== '1') {
                return ['ambient' => $fallback, 'debug' => array_merge($debug, ['reason' => 'amap_weather_status_not_ok'])];
            }
            $live = is_array($json['lives'] ?? null) ? ($json['lives'][0] ?? null) : null;
            if (! is_array($live)) {
                return ['ambient' => $fallback, 'debug' => array_merge($debug, ['reason' => 'weather_payload_invalid'])];
            }
            $text = trim((string) ($live['weather'] ?? '晴'));
            $cityName = trim((string) ($live['city'] ?? '')) ?: $city;
            $temp = $this->formatAmbientTemperature($live['temperature'] ?? null);

            return [
                'ambient' => array_filter([
                    'city_name' => $cityName ?: (string) ($cfg['default_city'] ?? '深圳'),
                    'weather_text' => $text !== '' ? $text : '晴',
                    'weather_code' => $this->normalizeWeatherCode('', $text),
                    'weather_icon_emoji' => $this->emojiByText($text),
                    'temperature' => $temp,
                ], static fn ($v) => $v !== null && $v !== ''),
                'debug' => array_merge($debug, ['ok' => true, 'fallback' => false, 'reason' => 'ok']),
            ];
        } catch (\Throwable $e) {
            return [
                'ambient' => $fallback,
                'debug' => array_merge($debug, ['reason' => 'exception', 'error' => mb_substr($e->getMessage(), 0, 160)]),
            ];
        }
    }

    private function formatAmbientTemperature(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $s = is_numeric($raw) ? (string) $raw : trim((string) $raw);
        if ($s === '') {
            return null;
        }
        if (str_contains($s, '°')) {
            return $s;
        }

        return $s.'°C';
    }

    /**
     * @return array{city_name:string,weather_text:string,weather_code:string,weather_icon_emoji:string}
     */
    private function fallback(string $city): array
    {
        return [
            'city_name' => $city ?: '深圳',
            'weather_text' => '晴',
            'weather_code' => 'sunny',
            'weather_icon_emoji' => '☀️',
        ];
    }

    private function emojiByQWeatherCode(string $icon): string
    {
        $code = (int) $icon;
        if ($code >= 300 && $code < 400) {
            return '🌧️';
        }
        if ($code >= 400 && $code < 500) {
            return '❄️';
        }
        if (in_array($code, [100, 150], true)) {
            return '☀️';
        }
        if (in_array($code, [101, 102, 103, 151, 152, 153], true)) {
            return '⛅';
        }
        if (in_array($code, [104, 154], true)) {
            return '☁️';
        }
        if ($code >= 500 && $code < 520) {
            return '🌫️';
        }

        return '☀️';
    }

    private function normalizeWeatherCode(string $icon, string $text): string
    {
        $code = (int) $icon;
        $t = mb_strtolower($text);
        if ($code >= 300 && $code < 400 || str_contains($t, '雨')) {
            return 'rain';
        }
        if ($code >= 400 && $code < 500 || str_contains($t, '雪')) {
            return 'snow';
        }
        if ($code >= 500 && $code < 520 || str_contains($t, '雾')) {
            return 'fog';
        }
        if (in_array($code, [100, 150], true) || str_contains($t, '晴')) {
            return 'sunny';
        }
        if (in_array($code, [101, 102, 103, 104, 151, 152, 153, 154], true) || str_contains($t, '云') || str_contains($t, '阴')) {
            return 'cloudy';
        }

        return 'clear';
    }

    private function emojiByText(string $text): string
    {
        $t = mb_strtolower($text);
        if (str_contains($t, '雨')) {
            return '🌧️';
        }
        if (str_contains($t, '雪')) {
            return '❄️';
        }
        if (str_contains($t, '雾') || str_contains($t, '霾')) {
            return '🌫️';
        }
        if (str_contains($t, '阴') || str_contains($t, '云')) {
            return '☁️';
        }
        if (str_contains($t, '晴')) {
            return '☀️';
        }

        return '☀️';
    }
}

