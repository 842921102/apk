<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRecommendationAnalytics;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class EffectReasonStyleTableWidget extends TableWidget
{
    use UsesRecommendationAnalytics;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 8;

    public function table(Table $table): Table
    {
        return $table
            ->heading('推荐理由 reason_style')
            ->description('近 7 天 · 按曝光排序')
            ->records(function (): Collection {
                $list = $this->analytics()->copyStylePerformance($this->now(), 7)['reason_style'] ?? [];

                return collect($list)->values()->map(fn (array $row, int $i): array => [
                    ...$row,
                    ArrayRecord::getKeyName() => 'reas-'.$i.'-'.($row['style'] ?? ''),
                ]);
            })
            ->columns([
                TextColumn::make('style')->label('风格')->wrap(),
                TextColumn::make('views')->label('曝光')->numeric()->alignEnd(),
                TextColumn::make('accepts')->label('想吃')->numeric()->alignEnd(),
                TextColumn::make('favorites')->label('收藏')->numeric()->alignEnd(),
                TextColumn::make('rejects')->label('拒绝')->numeric()->alignEnd(),
            ])
            ->paginated(false);
    }
}
