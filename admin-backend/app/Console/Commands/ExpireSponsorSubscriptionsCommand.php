<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * sponsor_until 已过期时同步 is_sponsor=false，便于后台与数据库一致。
 */
final class ExpireSponsorSubscriptionsCommand extends Command
{
    protected $signature = 'sponsor:expire';

    protected $description = '将赞助有效期已过的用户 is_sponsor 置为 false';

    public function handle(): int
    {
        $n = User::query()
            ->whereNotNull('sponsor_until')
            ->where('sponsor_until', '<', now())
            ->where('is_sponsor', true)
            ->update(['is_sponsor' => false]);

        if ($n > 0) {
            $this->info("已更新 {$n} 个过期赞助用户。");
        }

        return self::SUCCESS;
    }
}
