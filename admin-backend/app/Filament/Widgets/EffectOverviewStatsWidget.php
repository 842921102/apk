<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRecommendationAnalytics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EffectOverviewStatsWidget extends StatsOverviewWidget
{
    use UsesRecommendationAnalytics;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = '概览（今日）';

    protected ?string $description = '基于 recommendation_events 埋点；比率见下方「转化与反馈率」卡片（分母为今日推荐曝光）。';

    protected int|array|null $columns = 3;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $o = $this->analytics()->todayOverview($this->now());

        return [
            Stat::make('推荐曝光', $o['recommendation_views'])->icon(Heroicon::OutlinedEye),
            Stat::make('收藏（推荐链路）', $o['recipe_favorites_from_recommendation'])->icon(Heroicon::OutlinedHeart),
            Stat::make('查看做法', $o['recipe_views'])->icon(Heroicon::OutlinedBookOpen),
            Stat::make('换一换（reroll）', $o['rerolls'])->icon(Heroicon::OutlinedArrowPath),
            Stat::make('点选备选', $o['select_alternatives'])->icon(Heroicon::OutlinedSquares2x2),
            Stat::make('反馈次数', $o['feedbacks'])->icon(Heroicon::OutlinedChatBubbleLeftRight),
        ];
    }
}
