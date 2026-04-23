<?php

namespace App\Filament\Pages;

use App\Services\TencentCosService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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

class BusinessSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = '业务配置';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static ?int $navigationSort = 109;

    protected string $view = 'filament.pages.business-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public string $maskedSecretKey = '';

    public function mount(TencentCosService $service): void
    {
        $config = $service->getConfig();
        $this->maskedSecretKey = $this->maskSecret((string) ($config['secret_key'] ?? ''));
        $this->form->fill($this->toFormData($config));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('腾讯云对象存储 COS')
                    ->schema([
                        Section::make('基础配置')
                            ->schema([
                                TextInput::make('secret_id')->label('SecretId')->maxLength(200),
                                TextInput::make('masked_secret_key')
                                    ->label('SecretKey（掩码）')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('secret_key_update')
                                    ->label('更新 SecretKey（留空则不修改）')
                                    ->password()
                                    ->revealable(),
                                TextInput::make('bucket')->label('Bucket')->maxLength(200),
                                TextInput::make('region')->label('Region')->maxLength(120),
                                TextInput::make('domain')->label('访问域名 Domain')->maxLength(255)->columnSpanFull(),
                            ])
                            ->columns(2),
                        Section::make('上传配置')
                            ->schema([
                                TextInput::make('upload_prefix')->label('上传目录前缀')->maxLength(255),
                                Select::make('visibility')
                                    ->label('文件可见性')
                                    ->options(['public' => 'public', 'private' => 'private'])
                                    ->native(false),
                                TextInput::make('max_file_size')->label('最大文件大小（MB）')->numeric()->minValue(1)->maxValue(2048),
                                TagsInput::make('allowed_extensions')->label('允许扩展名'),
                                Toggle::make('use_https')->label('使用 HTTPS'),
                                Toggle::make('use_unique_name')->label('生成唯一文件名'),
                                Toggle::make('overwrite_enabled')->label('允许覆盖同名文件'),
                                Toggle::make('is_default')->label('设为默认配置'),
                            ])
                            ->columns(2),
                        Section::make('状态与测试')
                            ->schema([
                                Toggle::make('is_enabled')->label('启用配置'),
                                TextInput::make('last_tested_at')
                                    ->label('最后测试时间')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('last_test_result')
                                    ->label('最后测试结果')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function saveTencentCos(TencentCosService $service): void
    {
        $payload = $this->toServicePayload();
        $service->saveConfig($payload);

        $config = $service->getConfig();
        $this->maskedSecretKey = $this->maskSecret((string) ($config['secret_key'] ?? ''));
        $this->form->fill($this->toFormData($config));

        Notification::make()->title('COS 配置已保存')->success()->send();
    }

    public function testTencentCosConnection(TencentCosService $service): void
    {
        $config = $this->buildRuntimeConfig();
        $result = $service->testConnection($config);
        $saved = $service->getConfig();
        $this->form->fill($this->toFormData($saved));
        $notification = Notification::make()
            ->title($result['ok'] ? 'COS 连接测试成功' : 'COS 连接测试失败')
            ->body((string) $result['message']);
        if ($result['ok']) {
            $notification->success();
        } else {
            $notification->danger();
        }
        $notification->send();
    }

    public function testTencentCosUpload(TencentCosService $service): void
    {
        $config = $this->buildRuntimeConfig();
        $result = $service->testUpload($config);
        $saved = $service->getConfig();
        $this->form->fill($this->toFormData($saved));
        $notification = Notification::make()
            ->title($result['ok'] ? 'COS 测试上传成功' : 'COS 测试上传失败')
            ->body((string) ($result['file_url'] ?? $result['message']));
        if ($result['ok']) {
            $notification->success();
        } else {
            $notification->danger();
        }
        $notification->send();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRuntimeConfig(): array
    {
        $runtime = $this->toServicePayload();
        if (trim((string) ($runtime['secret_key'] ?? '')) !== '') {
            $runtime['secret_key'] = trim((string) $runtime['secret_key']);
        }

        return $runtime;
    }

    /**
     * @return array<string, mixed>
     */
    private function toServicePayload(): array
    {
        $state = is_array($this->form->getState()) ? $this->form->getState() : [];

        return [
            'secret_id' => (string) ($state['secret_id'] ?? ''),
            'secret_key' => (string) ($state['secret_key_update'] ?? ''),
            'bucket' => (string) ($state['bucket'] ?? ''),
            'region' => (string) ($state['region'] ?? ''),
            'domain' => (string) ($state['domain'] ?? ''),
            'upload_prefix' => (string) ($state['upload_prefix'] ?? ''),
            'use_https' => (bool) ($state['use_https'] ?? true),
            'use_unique_name' => (bool) ($state['use_unique_name'] ?? true),
            'visibility' => (string) ($state['visibility'] ?? 'public'),
            'allowed_extensions' => implode(',', array_values($state['allowed_extensions'] ?? [])),
            'max_file_size' => (int) ($state['max_file_size'] ?? 10),
            'overwrite_enabled' => (bool) ($state['overwrite_enabled'] ?? false),
            'is_default' => (bool) ($state['is_default'] ?? true),
            'is_enabled' => (bool) ($state['is_enabled'] ?? false),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function toFormData(array $config): array
    {
        return [
            'secret_id' => (string) ($config['secret_id'] ?? ''),
            'masked_secret_key' => $this->maskSecret((string) ($config['secret_key'] ?? '')),
            'secret_key_update' => '',
            'bucket' => (string) ($config['bucket'] ?? ''),
            'region' => (string) ($config['region'] ?? ''),
            'domain' => (string) ($config['domain'] ?? ''),
            'upload_prefix' => (string) ($config['upload_prefix'] ?? 'uploads'),
            'use_https' => (bool) ($config['use_https'] ?? true),
            'use_unique_name' => (bool) ($config['use_unique_name'] ?? true),
            'visibility' => (string) ($config['visibility'] ?? 'public'),
            'allowed_extensions' => array_values(array_filter(array_map(
                static fn (string $v): string => trim($v),
                explode(',', (string) ($config['allowed_extensions'] ?? '')),
            ), static fn (string $v): bool => $v !== '')),
            'max_file_size' => (int) ($config['max_file_size'] ?? 10),
            'overwrite_enabled' => (bool) ($config['overwrite_enabled'] ?? false),
            'is_default' => (bool) ($config['is_default'] ?? true),
            'is_enabled' => (bool) ($config['is_enabled'] ?? false),
            'last_tested_at' => (string) ($config['last_tested_at'] ?? '未测试'),
            'last_test_result' => (string) ($config['last_test_result'] ?? '暂无'),
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
