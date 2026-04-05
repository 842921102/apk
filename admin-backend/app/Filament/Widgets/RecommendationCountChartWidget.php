<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesAdminDashboard;
use Filament\Widgets\ChartWidget;

class RecommendationCountChartWidget extends ChartWidget
{
    use UsesAdminDashboard;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected ?string $heading = '最近 7 天 · 推荐次数';

    protected ?string $description = '历史日为完整自然日，今日截止到当前时刻';

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
        $series = $trends['recommend_trend'] ?? [];
        $labels = array_map(fn (array $row): string => (string) ($row['date'] ?? ''), $series);
        $data = array_map(fn (array $row): int|float => (int) ($row['value'] ?? 0), $series);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => '推荐次数',
                    'data' => $data,
                ],
            ],
        ];
    }
}
