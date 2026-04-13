<?php

namespace App\Services;

use App\Models\PaymentOrder;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class WechatPayService
{
    public function __construct(
        private PaymentConfigService $paymentConfigService,
    ) {}

    /**
     * @return array<string, string>
     */
    public function createJsapiPrepay(PaymentOrder $order): array
    {
        $config = $this->paymentConfigService->getWechatPayConfigOrFail();
        $requestBody = [
            'appid' => (string) $config['wx_mini_appid'],
            'mchid' => (string) $config['wx_pay_mchid'],
            'description' => (string) $order->title,
            'out_trade_no' => (string) $order->order_no,
            'notify_url' => (string) $config['wx_pay_notify_url'],
            'amount' => [
                'total' => (int) $order->amount_fen,
                'currency' => (string) ($order->currency ?: 'CNY'),
            ],
            'payer' => [
                'openid' => (string) $order->openid,
            ],
        ];

        if ($order->expired_at !== null) {
            $requestBody['time_expire'] = $order->expired_at->toIso8601String();
        }

        $urlPath = '/v3/pay/transactions/jsapi';
        $response = $this->wechatApiRequest(
            method: 'POST',
            urlPath: $urlPath,
            body: $requestBody,
            mchId: (string) $config['wx_pay_mchid'],
            serialNo: (string) $config['wx_pay_serial_no'],
            privateKeyPem: $this->loadPrivateKeyPem($config),
        );

        if (! $response->successful()) {
            throw new RuntimeException('微信统一下单失败: '.$response->status().' '.$response->body());
        }

        $json = $response->json();
        $prepayId = is_array($json) ? (string) ($json['prepay_id'] ?? '') : '';
        if ($prepayId === '') {
            throw new RuntimeException('微信统一下单返回缺少 prepay_id');
        }

        return $this->buildMiniProgramPayParams($prepayId, $config);
    }

    /**
     * 微信支付 v3 查单：按商户订单号查询 JSAPI 订单（用于回调未到时对齐状态）。
     *
     * @return array<string, mixed>|null
     */
    public function queryJsapiTransactionByOutTradeNo(string $outTradeNo): ?array
    {
        $config = $this->paymentConfigService->getWechatPayConfigOrFail();
        $mchid = (string) $config['wx_pay_mchid'];
        $encodedNo = rawurlencode($outTradeNo);
        $encodedMch = rawurlencode($mchid);
        $urlPath = "/v3/pay/transactions/out-trade-no/{$encodedNo}?mchid={$encodedMch}";

        $response = $this->wechatApiRequestGet(
            urlPath: $urlPath,
            mchId: $mchid,
            serialNo: (string) $config['wx_pay_serial_no'],
            privateKeyPem: $this->loadPrivateKeyPem($config),
        );

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public function parseNotify(array $headers, string $rawBody): array
    {
        $config = $this->paymentConfigService->getWechatPayConfigOrFail();
        $this->verifyNotifySignature($headers, $rawBody, $config);
        $body = json_decode($rawBody, true);
        if (! is_array($body)) {
            throw new RuntimeException('微信回调数据格式错误');
        }

        $resource = $body['resource'] ?? null;
        if (! is_array($resource)) {
            throw new RuntimeException('微信回调缺少 resource');
        }

        $ciphertext = (string) ($resource['ciphertext'] ?? '');
        $nonce = (string) ($resource['nonce'] ?? '');
        $associatedData = (string) ($resource['associated_data'] ?? '');
        if ($ciphertext === '' || $nonce === '') {
            throw new RuntimeException('微信回调加密字段不完整');
        }

        $resourceData = $this->decryptAesGcm(
            apiV3Key: (string) $config['wx_pay_api_v3_key'],
            nonce: $nonce,
            associatedData: $associatedData,
            ciphertext: $ciphertext,
        );
        $body['resource_data'] = $resourceData;

        return $body;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    private function buildMiniProgramPayParams(string $prepayId, array $config): array
    {
        $appId = (string) $config['wx_mini_appid'];
        $timeStamp = (string) time();
        $nonceStr = Str::random(32);
        $package = 'prepay_id='.$prepayId;
        $signType = 'RSA';
        $message = "{$appId}\n{$timeStamp}\n{$nonceStr}\n{$package}\n";
        $paySign = $this->signMessage($message, $this->loadPrivateKeyPem($config));

        return [
            'timeStamp' => $timeStamp,
            'nonceStr' => $nonceStr,
            'package' => $package,
            'paySign' => $paySign,
            'signType' => $signType,
            'prepayId' => $prepayId,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function wechatApiRequest(
        string $method,
        string $urlPath,
        array $body,
        string $mchId,
        string $serialNo,
        string $privateKeyPem,
    ): Response {
        $timestamp = (string) time();
        $nonce = Str::random(32);
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            throw new RuntimeException('微信请求体编码失败');
        }

        $message = strtoupper($method)."\n{$urlPath}\n{$timestamp}\n{$nonce}\n{$json}\n";
        $signature = $this->signMessage($message, $privateKeyPem);
        $token = sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',
            $mchId,
            $nonce,
            $timestamp,
            $serialNo,
            $signature,
        );

        return Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $token,
            'User-Agent' => 'what-to-eat-mini-pay/1.0',
        ])->timeout(15)->withOptions([
            'proxy' => null,
        ])->send(strtoupper($method), 'https://api.mch.weixin.qq.com'.$urlPath, [
            'body' => $json,
        ]);
    }

    private function wechatApiRequestGet(
        string $urlPath,
        string $mchId,
        string $serialNo,
        string $privateKeyPem,
    ): Response {
        $timestamp = (string) time();
        $nonce = Str::random(32);
        $message = "GET\n{$urlPath}\n{$timestamp}\n{$nonce}\n\n";
        $signature = $this->signMessage($message, $privateKeyPem);
        $token = sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',
            $mchId,
            $nonce,
            $timestamp,
            $serialNo,
            $signature,
        );

        return Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => $token,
            'User-Agent' => 'what-to-eat-mini-pay/1.0',
        ])->timeout(15)->withOptions([
            'proxy' => null,
        ])->get('https://api.mch.weixin.qq.com'.$urlPath);
    }

    private function signMessage(string $message, string $privateKeyPem): string
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if ($privateKey === false) {
            throw new RuntimeException('微信商户私钥读取失败');
        }

        $signature = '';
        $ok = openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('微信签名失败');
        }

        return base64_encode($signature);
    }

    /**
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $config
     */
    private function verifyNotifySignature(array $headers, string $rawBody, array $config): void
    {
        $timestamp = (string) ($headers['Wechatpay-Timestamp'] ?? $headers['wechatpay-timestamp'] ?? '');
        $nonce = (string) ($headers['Wechatpay-Nonce'] ?? $headers['wechatpay-nonce'] ?? '');
        $signature = (string) ($headers['Wechatpay-Signature'] ?? $headers['wechatpay-signature'] ?? '');
        if ($timestamp === '' || $nonce === '' || $signature === '') {
            throw new RuntimeException('微信回调签名头缺失');
        }

        $publicKeyPem = $this->loadWechatPlatformPublicKeyPem($config);
        $message = "{$timestamp}\n{$nonce}\n{$rawBody}\n";
        $verified = openssl_verify($message, base64_decode($signature), $publicKeyPem, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new RuntimeException('微信回调验签失败');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decryptAesGcm(string $apiV3Key, string $nonce, string $associatedData, string $ciphertext): array
    {
        $decoded = base64_decode($ciphertext, true);
        if ($decoded === false || strlen($decoded) < 16) {
            throw new RuntimeException('微信回调密文非法');
        }

        $authTag = substr($decoded, -16);
        $cipherRaw = substr($decoded, 0, -16);
        $plain = openssl_decrypt($cipherRaw, 'aes-256-gcm', $apiV3Key, OPENSSL_RAW_DATA, $nonce, $authTag, $associatedData);
        if (! is_string($plain) || $plain === '') {
            throw new RuntimeException('微信回调解密失败');
        }

        $payload = json_decode($plain, true);
        if (! is_array($payload)) {
            throw new RuntimeException('微信回调解密结果格式错误');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function loadPrivateKeyPem(array $config): string
    {
        $inline = trim((string) ($config['wx_pay_private_key_content'] ?? ''));
        if ($inline !== '') {
            return $inline;
        }

        $path = trim((string) ($config['wx_pay_private_key_path'] ?? ''));
        if ($path === '' || ! is_file($path)) {
            throw new RuntimeException('微信商户私钥文件不存在');
        }
        $content = file_get_contents($path);

        return is_string($content) ? $content : '';
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function loadWechatPlatformPublicKeyPem(array $config): string
    {
        $inline = trim((string) ($config['wx_pay_platform_public_key_content'] ?? ''));
        if ($inline !== '') {
            return $inline;
        }

        $path = trim((string) ($config['wx_pay_platform_public_key_path'] ?? ''));
        if ($path === '' || ! is_file($path)) {
            throw new RuntimeException('微信平台公钥未配置');
        }
        $content = file_get_contents($path);

        return is_string($content) ? $content : '';
    }
}
