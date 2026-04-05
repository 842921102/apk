<?php

namespace Database\Seeders;

use App\Models\BusinessConfig;
use Illuminate\Database\Seeder;

class WechatPayConfigSeeder extends Seeder
{
    public function run(): void
    {
        $row = BusinessConfig::query()->firstOrNew(['config_key' => 'wechat_pay']);
        $value = is_array($row->config_value) ? $row->config_value : [];

        $defaults = [
            'wx_mini_appid' => env('WECHAT_APP_ID', ''),
            'wx_pay_mchid' => env('WX_PAY_MCHID', ''),
            'wx_pay_api_v3_key' => env('WX_PAY_API_V3_KEY', ''),
            'wx_pay_private_key_path' => env('WX_PAY_PRIVATE_KEY_PATH', storage_path('certs/apiclient_key.pem')),
            'wx_pay_private_key_content' => '',
            'wx_pay_serial_no' => env('WX_PAY_SERIAL_NO', ''),
            'wx_pay_notify_url' => env('WX_PAY_NOTIFY_URL', config('app.url').'/api/pay/wechat/notify'),
            'wx_pay_platform_public_key_path' => env('WX_PAY_PLATFORM_PUBLIC_KEY_PATH', storage_path('certs/wechatpay_platform.pem')),
            'wx_pay_platform_public_key_content' => '',
            'order_expire_minutes' => 15,
        ];

        $row->config_name = '微信支付';
        $row->config_group = 'payment';
        $row->is_enabled = $row->is_enabled ?? false;
        $row->sort = $row->sort ?? 10;
        $row->remark = $row->remark ?? '微信小程序 JSAPI 支付配置，一期最小闭环占位。';
        $row->config_value = array_merge($defaults, $value);
        $row->save();
    }
}
