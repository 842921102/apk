<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRecommendationAnalytics;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class EffectTopDishesSkippedTableWidget extends TableWidget
{
    use UsesRecommendationAnalytics;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 10;

    public function table(Table $table): Table
    {
        return $table
            ->heading('菜品表现 · 最常被跳过')
            ->records(function (): Collection {
                $list = $this->analytics()->dishPerformance($this->now(), 7)['top_dishes_skipped'] ?? [];

                return collect($list)->values()->map(fn (array $row, int $i): array => [
                    ...$row,
                    ArrayRecord::getKeyName() => 'skip-dish-'.$i.'-'.($row['dish'] ?? ''),
                ]);
            })
            ->columns([
                TextColumn::make('dish')->label('菜名')->wrap(),
                TextColumn::make('count')->label('跳过次数')->numeric()->alignEnd(),
            ])
            ->paginated(false);
    }
}
