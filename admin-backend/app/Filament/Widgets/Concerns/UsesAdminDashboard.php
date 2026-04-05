<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\AdminDashboardService;
use Illuminate\Support\Carbon;

trait UsesAdminDashboard
{
    protected function dashboard(): AdminDashboardService
    {
        return app(AdminDashboardService::class);
    }

    protected function now(): Carbon
    {
        return Carbon::now();
    }

    /**
     * @param  array<string, mixed>  $overview
     */
    protected function compareDescription(array $overview, string $key): string
    {
        $y = $overview['yesterday_same_window'] ?? null;
        if (! is_array($y)) {
            return '';
        }

        $t = (int) ($overview[$key] ?? 0);
        $yp = (int) ($y[$key] ?? 0);

        if ($yp === 0 && $t === 0) {
            return '较昨日同时段：持平';
        }

        if ($yp === 0) {
            return '较昨日同时段：+'.$t.'（昨日为 0）';
        }

        $d = $t - $yp;
        $pct = round($d / $yp * 100, 1);
        $sign = $d >= 0 ? '+' : '';

        return '较昨日同时段：'.$sign.$d.'（'.$sign.$pct.'%）';
    }
}
