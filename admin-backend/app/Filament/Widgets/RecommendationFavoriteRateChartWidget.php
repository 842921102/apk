<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesAdminDashboard;
use Filament\Widgets\ChartWidget;

class RecommendationFavoriteRateChartWidget extends ChartWidget
{
    use UsesAdminDashboard;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected ?string $heading = '最近 7 天 · 收藏率';

    protected ?string $description = '全站收藏次数 / 当日推荐次数';

    protected ?string $maxHeight = '240px';

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $trends = $this->dashboard()->trends($this->now());
        $series = $trends['favorite_rate_trend'] ?? [];
        $labels = array_map(fn (array $row): string => (string) ($row['date'] ?? ''), $series);
        $data = array_map(function (array $row): ?float {
            $v = $row['value'] ?? null;

            return $v === null ? null : round((float) $v * 100, 2);
        }, $series);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => '收藏率（%）',
                    'data' => $data,
                ],
            ],
        ];
    }
}
