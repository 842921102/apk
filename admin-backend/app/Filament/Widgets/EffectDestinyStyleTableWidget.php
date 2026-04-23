<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRecommendationAnalytics;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class EffectDestinyStyleTableWidget extends TableWidget
{
    use UsesRecommendationAnalytics;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 7;

    public function table(Table $table): Table
    {
        return $table
            ->heading('食命文案 destiny_style')
            ->records(function (): Collection {
                $list = $this->analytics()->copyStylePerformance($this->now(), 7)['destiny_style'] ?? [];

                return collect($list)->values()->map(fn (array $row, int $i): array => [
                    ...$row,
                    ArrayRecord::getKeyName() => 'dest-'.$i.'-'.($row['style'] ?? ''),
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
