<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRecommendationAnalytics;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class EffectTrendTableWidget extends TableWidget
{
    use UsesRecommendationAnalytics;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('近 7 天趋势')
            ->description('按自然日统计；比率分母为当日推荐曝光。')
            ->records(function (): Collection {
                $rows = $this->analytics()->last7DaysTrend($this->now());

                return collect($rows)->values()->map(fn (array $row, int $i): array => [
                    ...$row,
                    ArrayRecord::getKeyName() => 'trend-'.$i.'-'.($row['date'] ?? ''),
                ]);
            })
            ->columns([
                TextColumn::make('date')->label('日期'),
                TextColumn::make('recommendation_views')
                    ->label('曝光')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('want_rate_pct')
                    ->label('想吃率')
                    ->alignEnd()
                    ->formatStateUsing(fn ($s) => $this->formatPct($s !== null ? (float) $s : null)),
                TextColumn::make('favorite_rate_pct')
                    ->label('收藏率')
                    ->alignEnd()
                    ->formatStateUsing(fn ($s) => $this->formatPct($s !== null ? (float) $s : null)),
                TextColumn::make('recipe_view_rate_pct')
                    ->label('做法查看率')
                    ->alignEnd()
                    ->formatStateUsing(fn ($s) => $this->formatPct($s !== null ? (float) $s : null)),
                TextColumn::make('reroll_rate_pct')
                    ->label('换推荐率')
                    ->alignEnd()
                    ->formatStateUsing(fn ($s) => $this->formatPct($s !== null ? (float) $s : null)),
            ])
            ->paginated(false);
    }
}
