<?php

namespace App\Filament\Pages;

use App\Services\MiniappWeatherAmbientService;
use App\Services\WeatherConfigService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WeatherSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static ?string $navigationLabel = '天气接口配置';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static ?int $navigationSort = 111;

    protected string $view = 'filament.pages.weather-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public string $maskedApiKey = '';

    public function mount(WeatherConfigService $service): void
    {
        $cfg = $service->getConfig();
        $this->maskedApiKey = $this->maskSecret((string) ($cfg['api_key'] ?? ''));
        $this->form->fill($this->toFormData($cfg));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('小程序首页天气')
                    ->schema([
                        Toggle::make('enabled')->label('启用天气服务'),
                        Select::make('provider')
                            ->label('天气供应商')
                            ->options([
                                'qweather' => '和风天气（QWeather）',
                                'amap' => '高德开放平台（Amap）',
                            ])
                            ->native(false)
                            ->default('qweather'),
                        TextInput::make('masked_api_key')
                            ->label('当前 API Key（掩码）')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('api_key_update')
                            ->label('更新 API Key（留空不修改）')
                            ->password()
                            ->revealable(),
                        TextInput::make('default_city')->label('默认城市')->required()->maxLength(64),
                        TextInput::make('default_location_id')->label('默认 Location ID（可选）')->maxLength(64),
                        TextInput::make('geo_base_url')->label('Geo API Base URL')->required()->url()->maxLength(255),
                        TextInput::make('weather_base_url')->label('Weather API Base URL')->required()->url()->maxLength(255),
                        TextInput::make('request_timeout_sec')
                            ->label('请求超时秒数')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(15),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(WeatherConfigService $service): void
    {
        $state = is_array($this->form->getState()) ? $this->form->getState() : [];
        $service->saveConfig([
            'enabled' => (bool) ($state['enabled'] ?? false),
            'provider' => (string) ($state['provider'] ?? 'qweather'),
            'api_key' => (string) ($state['api_key_update'] ?? ''),
            'default_city' => (string) ($state['default_city'] ?? '深圳'),
            'default_location_id' => (string) ($state['default_location_id'] ?? ''),
            'geo_base_url' => (string) ($state['geo_base_url'] ?? 'https://geoapi.qweather.com'),
            'weather_base_url' => (string) ($state['weather_base_url'] ?? 'https://devapi.qweather.com'),
            'request_timeout_sec' => (int) ($state['request_timeout_sec'] ?? 3),
        ]);
        $cfg = $service->getConfig();
        $this->maskedApiKey = $this->maskSecret((string) ($cfg['api_key'] ?? ''));
        $this->form->fill($this->toFormData($cfg));
        Notification::make()->title('天气配置已保存')->success()->send();
    }

    public function testAmbient(MiniappWeatherAmbientService $service): void
    {
        $result = $service->resolveWithDebug();
        $ambient = $result['ambient'];
        $debug = is_array($result['debug'] ?? null) ? $result['debug'] : [];
        $ok = (bool) ($debug['ok'] ?? false);
        $fallback = (bool) ($debug['fallback'] ?? true);
        $reason = (string) ($debug['reason'] ?? 'unknown');
        $geoHttp = isset($debug['geo_lookup_http']) ? (string) $debug['geo_lookup_http'] : '-';
        $weatherHttp = isset($debug['weather_http']) ? (string) $debug['weather_http'] : '-';
        $amapStatus = (string) ($debug['amap_status'] ?? '');
        $amapInfo = (string) ($debug['amap_info'] ?? '');
        $amapInfocode = (string) ($debug['amap_infocode'] ?? '');
        $err = (string) ($debug['error'] ?? '');
        $body = sprintf(
            "%s · %s %s\n状态: %s\n原因: %s\ngeo_http: %s, weather_http: %s%s%s",
            $ambient['city_name'],
            $ambient['weather_icon_emoji'],
            $ambient['weather_text'],
            ($ok && ! $fallback) ? 'success' : 'fallback',
            $reason,
            $geoHttp,
            $weatherHttp,
            $amapStatus !== '' || $amapInfo !== '' || $amapInfocode !== ''
                ? sprintf("\namap_status: %s, info: %s, infocode: %s", $amapStatus ?: '-', $amapInfo ?: '-', $amapInfocode ?: '-')
                : '',
            $err !== '' ? "\nerror: {$err}" : ''
        );
        $notification = Notification::make()
            ->title(($ok && ! $fallback) ? '测试成功' : '测试完成（已回退）')
            ->body($body);
        if ($ok && ! $fallback) {
            $notification->success();
        } else {
            $notification->warning();
        }
        $notification->send();
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @return array<string, mixed>
     */
    private function toFormData(array $cfg): array
    {
        return [
            'enabled' => (bool) ($cfg['enabled'] ?? false),
            'provider' => (string) ($cfg['provider'] ?? 'qweather'),
            'masked_api_key' => $this->maskSecret((string) ($cfg['api_key'] ?? '')),
            'api_key_update' => '',
            'default_city' => (string) ($cfg['default_city'] ?? '深圳'),
            'default_location_id' => (string) ($cfg['default_location_id'] ?? ''),
            'geo_base_url' => (string) ($cfg['geo_base_url'] ?? 'https://geoapi.qweather.com'),
            'weather_base_url' => (string) ($cfg['weather_base_url'] ?? 'https://devapi.qweather.com'),
            'request_timeout_sec' => (int) ($cfg['request_timeout_sec'] ?? 3),
        ];
    }

    private function maskSecret(string $secret): string
    {
        if ($secret === '') {
            return '未设置';
        }
        if (strlen($secret) <= 8) {
            return str_repeat('*', strlen($secret));
        }

        return substr($secret, 0, 4).str_repeat('*', max(strlen($secret) - 8, 4)).substr($secret, -4);
    }
}

