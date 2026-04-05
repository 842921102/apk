<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesAdminDashboard;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class RecommendationStyleStatsTableWidget extends TableWidget
{
    use UsesAdminDashboard;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('文案风格表现')
            ->description('reason_style / destiny_style · 收藏率为该风格下记录占比')
            ->records(function (): Collection {
                $rows = $this->dashboard()->rankings($this->now())['style_stats'] ?? [];

                return collect($rows)->values()->map(fn (array $row, int $i): array => [
                    ...$row,
                    ArrayRecord::getKeyName() => 'style-'.$i.'-'.($row['style_kind'] ?? '').'-'.($row['style_name'] ?? ''),
                ]);
            })
            ->columns([
                TextColumn::make('style_kind')
                    ->label('类型')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'reason_style' => '推荐理由',
                        'destiny_style' => '食命文案',
                        default => $state ?? '—',
                    }),
                TextColumn::make('style_name')
                    ->label('风格')
                    ->wrap(),
                TextColumn::make('use_count')
                    ->label('使用次数')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('favorite_rate')
                    ->label('收藏率')
                    ->alignEnd()
                    ->formatStateUsing(function ($state): string {
                        if ($state === null) {
                            return '—';
                        }

                        return round((float) $state * 100, 1).'%';
                    }),
            ])
            ->paginated(false);
    }
}
