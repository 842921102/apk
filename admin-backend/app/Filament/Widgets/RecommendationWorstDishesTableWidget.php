<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesAdminDashboard;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class RecommendationWorstDishesTableWidget extends TableWidget
{
    use UsesAdminDashboard;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('今日最常被换掉的菜 TOP5')
            ->description('基于换推荐事件中的上一道菜')
            ->records(function (): Collection {
                $rows = $this->dashboard()->rankings($this->now())['worst_dishes'] ?? [];

                return collect($rows)->values()->map(fn (array $row, int $i): array => [
                    ...$row,
                    ArrayRecord::getKeyName() => 'worst-'.$i.'-'.($row['dish_name'] ?? ''),
                ]);
            })
            ->columns([
                TextColumn::make('dish_name')
                    ->label('菜名')
                    ->wrap(),
                TextColumn::make('reroll_count')
                    ->label('被换次数')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
