<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRecommendationAnalytics;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class EffectImpressionTagsTableWidget extends TableWidget
{
    use UsesRecommendationAnalytics;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('曝光最多标签')
            ->description('近 7 天 · recommendation_view')
            ->records(function (): Collection {
                $list = $this->analytics()->tagPerformance($this->now(), 7)['top_impression_tags'] ?? [];

                return collect($list)->values()->map(fn (array $row, int $i): array => [
                    ...$row,
                    ArrayRecord::getKeyName() => 'imp-'.$i.'-'.($row['tag'] ?? ''),
                ]);
            })
            ->columns([
                TextColumn::make('tag')->label('标签')->wrap(),
                TextColumn::make('count')->label('次数')->numeric()->alignEnd(),
            ])
            ->paginated(false);
    }
}
