<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesAdminDashboard;
use Filament\Widgets\ChartWidget;

class RecommendationRerollRateChartWidget extends ChartWidget
{
    use UsesAdminDashboard;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = '最近 7 天 · 换推荐率';

    protected ?string $description = '换推荐事件次数 / 当日推荐次数';

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
        $series = $trends['reroll_rate_trend'] ?? [];
        $labels = array_map(fn (array $row): string => (string) ($row['date'] ?? ''), $series);
        $data = array_map(function (array $row): ?float {
            $v = $row['value'] ?? null;

            return $v === null ? null : round((float) $v * 100, 2);
        }, $series);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => '换推荐率（%）',
                    'data' => $data,
                ],
            ],
        ];
    }
}
