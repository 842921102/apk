<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesAdminDashboard;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecommendationHealthStatsWidget extends StatsOverviewWidget
{
    use UsesAdminDashboard;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 9;

    protected ?string $heading = '系统健康监控';

    protected ?string $description = '今日 00:00 至当前 · 非零时请关注';

    protected int|array|null $columns = 4;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $h = $this->dashboard()->health($this->now());

        $mk = fn (string $key, string $label, $icon) => Stat::make($label, (int) ($h[$key] ?? 0))
            ->icon($icon)
            ->color((int) ($h[$key] ?? 0) > 0 ? 'warning' : 'gray');

        return [
            $mk('fallback_count', 'AI fallback', Heroicon::OutlinedExclamationTriangle),
            $mk('error_count', '推荐失败 / error', Heroicon::OutlinedXCircle),
            $mk('no_recipe_count', '无做法数据', Heroicon::OutlinedBookOpen),
            $mk('default_profile_count', '默认画像推荐', Heroicon::OutlinedUserCircle),
        ];
    }
}
