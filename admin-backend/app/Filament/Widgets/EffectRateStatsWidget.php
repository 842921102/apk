<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRecommendationAnalytics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EffectRateStatsWidget extends StatsOverviewWidget
{
    use UsesRecommendationAnalytics;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = '转化与反馈率（今日）';

    protected ?string $description = '分母：今日 recommendation_view 曝光次数。';

    protected int|array|null $columns = 3;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $o = $this->analytics()->todayOverview($this->now());

        return [
            Stat::make('想吃率', $this->formatPct($o['want_rate_pct'] ?? null))->icon(Heroicon::OutlinedFaceSmile),
            Stat::make('收藏率（推荐链路）', $this->formatPct($o['favorite_rate_pct'] ?? null))->icon(Heroicon::OutlinedHeart),
            Stat::make('查看做法率（带推荐记录）', $this->formatPct($o['recipe_view_rate_pct'] ?? null))->icon(Heroicon::OutlinedChartBar),
            Stat::make('换推荐率', $this->formatPct($o['reroll_rate_pct'] ?? null))->icon(Heroicon::OutlinedArrowPath),
            Stat::make('今天不想吃率', $this->formatPct($o['not_today_rate_pct'] ?? null))->icon(Heroicon::OutlinedNoSymbol),
            Stat::make('不适合我率', $this->formatPct($o['not_suitable_rate_pct'] ?? null))->icon(Heroicon::OutlinedExclamationTriangle),
        ];
    }
}
