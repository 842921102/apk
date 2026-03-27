<?php

namespace App\Filament\Widgets\FeatureData;

use App\Models\FeatureDataRecord;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

abstract class BaseFeatureDataStats extends StatsOverviewWidget
{
    abstract protected function featureType(): string;

    protected function getStats(): array
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::tomorrow();

        $todayTotal = FeatureDataRecord::query()
            ->where('feature_type', $this->featureType())
            ->where('created_at', '>=', $todayStart)
            ->where('created_at', '<', $todayEnd)
            ->count();

        $todaySuccess = FeatureDataRecord::query()
            ->where('feature_type', $this->featureType())
            ->where('status', 'success')
            ->where('created_at', '>=', $todayStart)
            ->where('created_at', '<', $todayEnd)
            ->count();

        $todayFailed = FeatureDataRecord::query()
            ->where('feature_type', $this->featureType())
            ->where('status', 'failed')
            ->where('created_at', '>=', $todayStart)
            ->where('created_at', '<', $todayEnd)
            ->count();

        $successRate = $todayTotal > 0
            ? number_format(($todaySuccess / $todayTotal) * 100, 1).'%'
            : '0.0%';

        return [
            Stat::make('今日请求数', (string) $todayTotal)
                ->description('当天总调用次数')
                ->color('primary'),
            Stat::make('成功数', (string) $todaySuccess)
                ->description('当天成功调用')
                ->color('success'),
            Stat::make('失败数', (string) $todayFailed)
                ->description('当天失败调用')
                ->color('danger'),
            Stat::make('成功率', $successRate)
                ->description('成功数 / 总请求')
                ->color($todayFailed > 0 ? 'warning' : 'success'),
        ];
    }
}

