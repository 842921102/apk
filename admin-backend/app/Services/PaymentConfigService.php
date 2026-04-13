<?php

namespace App\Services;

use App\Models\BusinessConfig;
use Illuminate\Support\Arr;
use RuntimeException;

final class PaymentConfigService
{
    public const CONFIG_KEY = 'wechat_pay';

    /**
     * @return array<string, mixed>
     */
    public function getWechatPayConfig(): array
    {
        $row = BusinessConfig::query()->where('config_key', self::CONFIG_KEY)->first();
        $value = is_array($row?->config_value) ? $row->config_value : [];
        $defaults = $this->defaultConfig();

        return array_merge($defaults, Arr::only($value, array_keys($defaults)));
    }

    /**
     * @return array<string, mixed>
     */
    public function getWechatPayConfigOrFail(): array
    {
        $config = $this->getWechatPayConfig();
        $required = [
            'wx_mini_appid',
            'wx_pay_mchid',
            'wx_pay_api_v3_key',
            'wx_pay_serial_no',
            'wx_pay_notify_url',
        ];

        foreach ($required as $key) {
            if (trim((string) ($config[$key] ?? '')) === '') {
                throw new RuntimeException("微信支付配置缺失: {$key}");
            }
        }

        if (! $this->hasMerchantPrivateKeyConfigured($config)) {
            throw new RuntimeException('微信支付配置缺失: 商户私钥（请上传 .pem、粘贴 PEM 文本，或填写服务器上可读的文件路径）');
        }

        if (! $this->hasWechatPlatformPublicKeyConfigured($config)) {
            throw new RuntimeException('微信支付配置缺失: 微信平台公钥（请上传、粘贴 PEM，或填写服务器路径；回调验签需要）');
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function hasMerchantPrivateKeyConfigured(array $config): bool
    {
        if (trim((string) ($config['wx_pay_private_key_content'] ?? '')) !== '') {
            return true;
        }
        $path = trim((string) ($config['wx_pay_private_key_path'] ?? ''));

        return $path !== '' && is_file($path);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function hasWechatPlatformPublicKeyConfigured(array $config): bool
    {
        if (trim((string) ($config['wx_pay_platform_public_key_content'] ?? '')) !== '') {
            return true;
        }
        $path = trim((string) ($config['wx_pay_platform_public_key_path'] ?? ''));

        return $path !== '' && is_file($path);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveWechatPayConfig(array $payload): BusinessConfig
    {
        $current = $this->getWechatPayConfig();
        $merged = array_merge($current, Arr::only($payload, array_keys($this->defaultConfig())));

        $row = BusinessConfig::query()->firstOrNew(['config_key' => self::CONFIG_KEY]);
        $row->config_name = '微信支付';
        $row->config_group = 'payment';
        $row->is_enabled = (bool) ($payload['is_enabled'] ?? $row->is_enabled ?? false);
        $row->sort = (int) ($payload['sort'] ?? $row->sort ?? 10);
        $row->remark = isset($payload['remark']) ? (string) $payload['remark'] : ($row->remark ?? '微信小程序 JSAPI 支付配置');
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
            'wx_mini_appid' => (string) env('WECHAT_APP_ID', ''),
            'wx_pay_mchid' => (string) env('WX_PAY_MCHID', ''),
            'wx_pay_api_v3_key' => (string) env('WX_PAY_API_V3_KEY', ''),
            'wx_pay_private_key_path' => (string) env('WX_PAY_PRIVATE_KEY_PATH', ''),
            'wx_pay_private_key_content' => (string) env('WX_PAY_PRIVATE_KEY_CONTENT', ''),
            'wx_pay_serial_no' => (string) env('WX_PAY_SERIAL_NO', ''),
            'wx_pay_notify_url' => $this->defaultNotifyUrl(),
            // 用于验证回调签名（二选一）
            'wx_pay_platform_public_key_path' => (string) env('WX_PAY_PLATFORM_PUBLIC_KEY_PATH', ''),
            'wx_pay_platform_public_key_content' => (string) env('WX_PAY_PLATFORM_PUBLIC_KEY_CONTENT', ''),
            'order_expire_minutes' => 15,
        ];
    }

    private function defaultNotifyUrl(): string
    {
        $fromEnv = env('WX_PAY_NOTIFY_URL');
        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            return trim($fromEnv);
        }

        $base = rtrim((string) env('APP_URL', ''), '/');
        if ($base === '') {
            return '';
        }

        return $base.'/api/pay/wechat/notify';
    }
}
