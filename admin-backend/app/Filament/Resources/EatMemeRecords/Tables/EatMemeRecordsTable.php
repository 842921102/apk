<?php

namespace App\Filament\Resources\EatMemeRecords\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EatMemeRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferFilters(false)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('编号')->sortable(),
                TextColumn::make('channel')->label('渠道')->badge(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'success' ? 'success' : ($state === 'failed' ? 'danger' : 'gray')),
                TextColumn::make('result_title')->label('推荐标题')->searchable()->wrap()->placeholder('—')->limit(36)->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('result_cuisine')->label('菜系')->placeholder('—'),
                TextColumn::make('taste')->label('口味')->placeholder('—'),
                TextColumn::make('people')->label('人数')->placeholder('—'),
                TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
            ])
            ->filters([
                Filter::make('keyword')
                    ->label('关键词')
                    ->schema([
                        TextInput::make('value')->label('标题关键词'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $v = trim((string) ($data['value'] ?? ''));

                        return $query->when($v !== '', fn (Builder $q) => $q->where('result_title', 'like', '%'.$v.'%'));
                    }),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'success' => '成功',
                        'failed' => '失败',
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()->label('查看')->modalWidth('5xl'),
                DeleteAction::make()->label('删除'),
            ]);
    }
}
