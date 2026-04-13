<?php

namespace App\Console\Commands;

use App\Services\PaymentOrderService;
use Illuminate\Console\Command;

final class CloseExpiredPaymentOrdersCommand extends Command
{
    protected $signature = 'payment:close-expired-orders';

    protected $description = '将已过期的待支付支付单（payment_orders）标记为 closed';

    public function handle(PaymentOrderService $paymentOrderService): int
    {
        $n = $paymentOrderService->closeExpiredPendingPaymentOrders();
        if ($n > 0) {
            $this->info("已关闭 {$n} 笔过期待支付订单。");
        }

        return self::SUCCESS;
    }
}
