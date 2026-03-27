<?php

namespace App\Filament\Resources\Favorites\Tables;

use App\Models\Favorite;
use App\Support\AdminActionLogger;
use App\Support\FavoriteSourceType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FavoritesTable
{
    public static function configure(Table $table): Table
    {
        $sourceLabels = [
            FavoriteSourceType::TodayEat->value => '吃什么',
            FavoriteSourceType::TableDesign->value => '满汉全席',
            FavoriteSourceType::FortuneCooking->value => '玄学厨房',
            FavoriteSourceType::SauceDesign->value => '酱料大师',
            FavoriteSourceType::Gallery->value => '图鉴',
        ];

        return $table
            ->deferFilters(false)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('source_type')
                    ->label('来源')
                    ->formatStateUsing(fn (?string $state): string => $state ? ($sourceLabels[$state] ?? $state) : '—')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('用户')
                    ->description(fn (Favorite $record): string => (string) $record->user?->email)
                    ->searchable(),
                TextColumn::make('cuisine')
                    ->label('菜系')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('title')
                    ->label('标题')
                    ->schema([
                        TextInput::make('keyword')->label('关键词'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $k = isset($data['keyword']) ? trim((string) $data['keyword']) : '';

                        return $query->when(
                            $k !== '',
                            fn (Builder $q): Builder => $q->where('title', 'like', '%'.$k.'%'),
                        );
                    }),
                SelectFilter::make('source_type')
                    ->label('来源类型')
                    ->options($sourceLabels),
                Filter::make('user_id')
                    ->label('用户 ID')
                    ->schema([
                        TextInput::make('id')->numeric()->label('精确 ID'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['id'] ?? null),
                            fn (Builder $q): Builder => $q->where('favorites.user_id', (int) $data['id']),
                        );
                    }),
                Filter::make('created_at')
                    ->label('创建时间')
                    ->schema([
                        DatePicker::make('from')->label('从'),
                        DatePicker::make('until')->label('至'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $q) => $q->whereDate('created_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $q) => $q->whereDate('created_at', '<=', $data['until']),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()
                    ->modalWidth('5xl'),
                DeleteAction::make()
                    ->before(function (Favorite $record): void {
                        AdminActionLogger::record('favorite.deleted', $record, [
                            'owner_user_id' => $record->user_id,
                            'title' => $record->title,
                        ]);
                    })
                    ->visible(fn (Favorite $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->can('deleteAny', Favorite::class) ?? false),
                ]),
            ]);
    }
}
