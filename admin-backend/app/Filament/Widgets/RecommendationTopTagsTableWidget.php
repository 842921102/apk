<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesAdminDashboard;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class RecommendationTopTagsTableWidget extends TableWidget
{
    use UsesAdminDashboard;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('今日高表现标签 TOP5')
            ->description('标签来自推荐记录；收藏关联为记录 is_favorited 简化口径')
            ->records(function (): Collection {
                $rows = $this->dashboard()->rankings($this->now())['top_tags'] ?? [];

                return collect($rows)->values()->map(fn (array $row, int $i): array => [
                    ...$row,
                    ArrayRecord::getKeyName() => 'tag-'.$i.'-'.($row['tag_name'] ?? ''),
                ]);
            })
            ->columns([
                TextColumn::make('tag_name')
                    ->label('标签')
                    ->wrap(),
                TextColumn::make('hit_count')
                    ->label('命中次数')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('favorite_count')
                    ->label('收藏关联')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
