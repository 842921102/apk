<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesAdminDashboard;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class RecommendationTopDishesTableWidget extends TableWidget
{
    use UsesAdminDashboard;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('今日最受欢迎推荐菜 TOP5')
            ->description('按收藏 + 查看做法综合排序')
            ->records(function (): Collection {
                $rows = $this->dashboard()->rankings($this->now())['top_dishes'] ?? [];

                return collect($rows)->values()->map(fn (array $row, int $i): array => [
                    ...$row,
                    ArrayRecord::getKeyName() => 'dish-'.$i.'-'.($row['dish_name'] ?? ''),
                ]);
            })
            ->columns([
                TextColumn::make('dish_name')
                    ->label('菜名')
                    ->wrap(),
                TextColumn::make('favorite_count')
                    ->label('收藏')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('recipe_view_count')
                    ->label('查看做法')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
