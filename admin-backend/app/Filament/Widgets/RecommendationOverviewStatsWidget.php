<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesAdminDashboard;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecommendationOverviewStatsWidget extends StatsOverviewWidget
{
    use UsesAdminDashboard;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected ?string $heading = '核心概览';

    protected ?string $description = '今日 00:00 至当前 · 登录用户 · 库内统计';

    protected int|array|null $columns = 3;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $o = $this->dashboard()->overview($this->now());

        return [
            Stat::make('今日推荐次数', $o['recommend_count'])
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedChartBar),
            Stat::make('今日活跃用户', $o['active_users'])
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedUsers),
            Stat::make('今日收藏次数', $o['favorites_count'])
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedHeart),
            Stat::make('今日查看做法', $o['recipe_view_count'])
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedEye),
            Stat::make('今日换推荐', $o['reroll_count'])
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedArrowPath),
            Stat::make('今日反馈', $o['feedback_count'])
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->icon(Heroicon::OutlinedChatBubbleLeftRight),
        ];
    }
}
