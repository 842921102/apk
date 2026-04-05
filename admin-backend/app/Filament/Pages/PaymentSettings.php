<?php

namespace App\Filament\Pages;

use App\Services\PaymentConfigService;
use BackedEnum;
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

class PaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = '支付设置';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static ?int $navigationSort = 110;

    protected string $view = 'filament.pages.payment-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(PaymentConfigService $service): void
    {
        $config = $service->getWechatPayConfig();
        $this->form->fill($this->toFormData($config));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('微信小程序支付（JSAPI）')
                    ->description('一期最小闭环配置：仅覆盖下单、拉起支付、回调验签。')
                    ->schema([
                        Toggle::make('is_enabled')->label('启用微信支付'),
                        TextInput::make('order_expire_minutes')
                            ->label('订单过期分钟数')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->default(15),
                        TextInput::make('wx_mini_appid')->label('小程序 AppID')->required()->maxLength(64),
                        TextInput::make('wx_pay_mchid')->label('商户号 MchID')->required()->maxLength(64),
                        TextInput::make('wx_pay_serial_no')->label('商户证书序列号 SerialNo')->required()->maxLength(128),
                        TextInput::make('wx_pay_notify_url')->label('支付回调地址 Notify URL')->required()->url()->maxLength(255)->columnSpanFull(),
                        TextInput::make('wx_pay_api_v3_key')
                            ->label('API v3 Key')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(64),
                        TextInput::make('wx_pay_private_key_path')->label('商户私钥文件路径')->maxLength(255)->columnSpanFull(),
                        TextInput::make('wx_pay_private_key_content')
                            ->label('商户私钥内容 PEM（可选，优先于路径）')
                            ->placeholder('-----BEGIN PRIVATE KEY----- ...')
                            ->columnSpanFull(),
                        TextInput::make('wx_pay_platform_public_key_path')->label('微信平台公钥文件路径')->maxLength(255)->columnSpanFull(),
                        TextInput::make('wx_pay_platform_public_key_content')
                            ->label('微信平台公钥内容 PEM（可选，优先于路径）')
                            ->placeholder('-----BEGIN PUBLIC KEY----- ...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(PaymentConfigService $service): void
    {
        $state = is_array($this->form->getState()) ? $this->form->getState() : [];
        $service->saveWechatPayConfig([
            'is_enabled' => (bool) ($state['is_enabled'] ?? false),
            'wx_mini_appid' => (string) ($state['wx_mini_appid'] ?? ''),
            'wx_pay_mchid' => (string) ($state['wx_pay_mchid'] ?? ''),
            'wx_pay_api_v3_key' => (string) ($state['wx_pay_api_v3_key'] ?? ''),
            'wx_pay_private_key_path' => (string) ($state['wx_pay_private_key_path'] ?? ''),
            'wx_pay_private_key_content' => (string) ($state['wx_pay_private_key_content'] ?? ''),
            'wx_pay_serial_no' => (string) ($state['wx_pay_serial_no'] ?? ''),
            'wx_pay_notify_url' => (string) ($state['wx_pay_notify_url'] ?? ''),
            'wx_pay_platform_public_key_path' => (string) ($state['wx_pay_platform_public_key_path'] ?? ''),
            'wx_pay_platform_public_key_content' => (string) ($state['wx_pay_platform_public_key_content'] ?? ''),
            'order_expire_minutes' => (int) ($state['order_expire_minutes'] ?? 15),
        ]);

        Notification::make()
            ->title('支付配置已保存')
            ->success()
            ->send();
    }

    public function validateConfig(PaymentConfigService $service): void
    {
        try {
            $service->getWechatPayConfigOrFail();
            Notification::make()
                ->title('支付配置校验通过')
                ->success()
                ->send();
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('支付配置校验失败')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function toFormData(array $config): array
    {
        return [
            'is_enabled' => (bool) ($config['is_enabled'] ?? false),
            'wx_mini_appid' => (string) ($config['wx_mini_appid'] ?? ''),
            'wx_pay_mchid' => (string) ($config['wx_pay_mchid'] ?? ''),
            'wx_pay_api_v3_key' => (string) ($config['wx_pay_api_v3_key'] ?? ''),
            'wx_pay_private_key_path' => (string) ($config['wx_pay_private_key_path'] ?? ''),
            'wx_pay_private_key_content' => (string) ($config['wx_pay_private_key_content'] ?? ''),
            'wx_pay_serial_no' => (string) ($config['wx_pay_serial_no'] ?? ''),
            'wx_pay_notify_url' => (string) ($config['wx_pay_notify_url'] ?? ''),
            'wx_pay_platform_public_key_path' => (string) ($config['wx_pay_platform_public_key_path'] ?? ''),
            'wx_pay_platform_public_key_content' => (string) ($config['wx_pay_platform_public_key_content'] ?? ''),
            'order_expire_minutes' => (int) ($config['order_expire_minutes'] ?? 15),
        ];
    }
}
