<?php

namespace App\Filament\Pages;

use App\Services\PaymentConfigService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
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
        $this->form->fill(array_merge($this->toFormData($config), [
            'upload_private_key_pem' => null,
            'upload_platform_public_key_pem' => null,
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('微信小程序支付（JSAPI）')
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
                        TextInput::make('wx_pay_notify_url')
                            ->label('支付回调地址 Notify URL')
                            ->required()
                            ->url()
                            ->maxLength(512)
                            ->columnSpanFull(),
                        TextInput::make('wx_pay_api_v3_key')
                            ->label('API v3 Key')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(64),
                    ])
                    ->columns(2),

                Section::make('商户证书（推荐：上传至服务器）')
                    ->schema([
                        FileUpload::make('upload_private_key_pem')
                            ->label('上传商户私钥 apiclient_key.pem')
                            ->disk('local')
                            ->directory('private/wechat-pay')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/x-x509-ca-cert', 'application/pkcs8', 'text/plain', '.pem'])
                            ->maxSize(64)
                            ->downloadable(false)
                            ->openable(false)
                            ->getUploadedFileNameForStorageUsing(fn () => 'apiclient_key.pem')
                            ->columnSpanFull(),
                        Textarea::make('wx_pay_private_key_content')
                            ->label('或直接粘贴商户私钥 PEM')
                            ->placeholder('-----BEGIN PRIVATE KEY----- ...')
                            ->rows(6)
                            ->columnSpanFull(),

                        FileUpload::make('upload_platform_public_key_pem')
                            ->label('上传微信平台公钥 wechatpay_platform.pem')
                            ->disk('local')
                            ->directory('private/wechat-pay')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/x-x509-ca-cert', 'text/plain', '.pem'])
                            ->maxSize(64)
                            ->downloadable(false)
                            ->openable(false)
                            ->getUploadedFileNameForStorageUsing(fn () => 'wechatpay_platform.pem')
                            ->columnSpanFull(),
                        Textarea::make('wx_pay_platform_public_key_content')
                            ->label('或直接粘贴微信平台公钥 PEM')
                            ->placeholder('-----BEGIN PUBLIC KEY----- ...')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),

                Section::make('高级：仅当证书已在服务器固定路径时')
                    ->collapsed()
                    ->schema([
                        TextInput::make('wx_pay_private_key_path')
                            ->label('商户私钥文件绝对路径')
                            ->maxLength(512)
                            ->columnSpanFull(),
                        TextInput::make('wx_pay_platform_public_key_path')
                            ->label('微信平台公钥文件绝对路径')
                            ->maxLength(512)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(PaymentConfigService $service): void
    {
        $state = is_array($this->form->getState()) ? $this->form->getState() : [];

        $privatePath = (string) ($state['wx_pay_private_key_path'] ?? '');
        $privateContent = (string) ($state['wx_pay_private_key_content'] ?? '');
        $platformPath = (string) ($state['wx_pay_platform_public_key_path'] ?? '');
        $platformContent = (string) ($state['wx_pay_platform_public_key_content'] ?? '');

        $uploadPrivate = $state['upload_private_key_pem'] ?? null;
        if (is_string($uploadPrivate) && $uploadPrivate !== '') {
            $abs = Storage::disk('local')->path($uploadPrivate);
            if (is_file($abs)) {
                $privatePath = $abs;
                $privateContent = '';
            }
        }

        $uploadPlatform = $state['upload_platform_public_key_pem'] ?? null;
        if (is_string($uploadPlatform) && $uploadPlatform !== '') {
            $abs = Storage::disk('local')->path($uploadPlatform);
            if (is_file($abs)) {
                $platformPath = $abs;
                $platformContent = '';
            }
        }

        $service->saveWechatPayConfig([
            'is_enabled' => (bool) ($state['is_enabled'] ?? false),
            'wx_mini_appid' => (string) ($state['wx_mini_appid'] ?? ''),
            'wx_pay_mchid' => (string) ($state['wx_pay_mchid'] ?? ''),
            'wx_pay_api_v3_key' => (string) ($state['wx_pay_api_v3_key'] ?? ''),
            'wx_pay_private_key_path' => $privatePath,
            'wx_pay_private_key_content' => $privateContent,
            'wx_pay_serial_no' => (string) ($state['wx_pay_serial_no'] ?? ''),
            'wx_pay_notify_url' => (string) ($state['wx_pay_notify_url'] ?? ''),
            'wx_pay_platform_public_key_path' => $platformPath,
            'wx_pay_platform_public_key_content' => $platformContent,
            'order_expire_minutes' => (int) ($state['order_expire_minutes'] ?? 15),
        ]);

        $this->form->fill(array_merge(
            $this->toFormData($service->getWechatPayConfig()),
            [
                'upload_private_key_pem' => null,
                'upload_platform_public_key_pem' => null,
            ],
        ));

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
