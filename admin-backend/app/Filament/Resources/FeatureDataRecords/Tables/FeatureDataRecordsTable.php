<?php

namespace App\Filament\Resources\FeatureDataRecords\Tables;

use App\Models\FeatureDataRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeatureDataRecordsTable
{
    public static function configureTableMenu(Table $table): Table
    {
        return self::base($table)->columns([
            TextColumn::make('id')->label('编号')->sortable()->copyable(),
            TextColumn::make('sub_type')->label('动作')->badge()->placeholder('—'),
            TextColumn::make('title')->label('方案标题')->searchable()->wrap()->placeholder('—')->limit(32)->tooltip(fn (?string $state): ?string => $state),
            TextColumn::make('menu_count')
                ->label('生成菜数')
                ->state(fn (FeatureDataRecord $record): int => count(data_get($record->input_payload, 'config.menus', [])))
                ->sortable(),
            TextColumn::make('category_count')
                ->label('菜系数')
                ->state(fn (FeatureDataRecord $record): int => count(data_get($record->input_payload, 'config.categories', []))),
            TextColumn::make('status')->label('状态')->badge()->color(fn (?string $state): string => $state === 'success' ? 'success' : ($state === 'failed' ? 'danger' : 'gray')),
            TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
        ]);
    }

    public static function configureFortuneCooking(Table $table): Table
    {
        return self::base($table)->columns([
            TextColumn::make('id')->label('编号')->sortable()->copyable(),
            TextColumn::make('sub_type')->label('运势类型')->badge()->placeholder('—'),
            TextColumn::make('title')->label('结果标题')->searchable()->wrap()->placeholder('—')->limit(32)->tooltip(fn (?string $state): ?string => $state),
            TextColumn::make('mood')
                ->label('心情')
                ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'mood', '—')),
            TextColumn::make('number')
                ->label('幸运数字')
                ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'number', '—')),
            TextColumn::make('status')->label('状态')->badge()->color(fn (?string $state): string => $state === 'success' ? 'success' : ($state === 'failed' ? 'danger' : 'gray')),
            TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
        ]);
    }

    public static function configureSauceDesign(Table $table): Table
    {
        return self::base($table)->columns([
            TextColumn::make('id')->label('编号')->sortable()->copyable(),
            TextColumn::make('sub_type')->label('动作')->badge()->placeholder('—'),
            TextColumn::make('title')->label('酱料/方案')->searchable()->wrap()->placeholder('—')->limit(32)->tooltip(fn (?string $state): ?string => $state),
            TextColumn::make('taste')
                ->label('口味偏好')
                ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'preferences.taste', '—')),
            TextColumn::make('scene')
                ->label('使用场景')
                ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'preferences.scene', '—')),
            TextColumn::make('status')->label('状态')->badge()->color(fn (?string $state): string => $state === 'success' ? 'success' : ($state === 'failed' ? 'danger' : 'gray')),
            TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
        ]);
    }

    public static function configureGallery(Table $table): Table
    {
        return self::base($table)->columns([
            TextColumn::make('id')->label('编号')->sortable()->copyable(),
            TextColumn::make('sub_type')->label('动作')->badge()->placeholder('—'),
            TextColumn::make('title')->label('标题')->searchable()->placeholder('—')->limit(32)->tooltip(fn (?string $state): ?string => $state),
            TextColumn::make('item_count')
                ->label('图鉴数量')
                ->state(fn (FeatureDataRecord $record): string => (string) ($record->result_summary ?? '—'))
                ->formatStateUsing(fn (string $state): string => str_starts_with($state, 'items:') ? $state : '—'),
            TextColumn::make('status')->label('状态')->badge()->color(fn (?string $state): string => $state === 'success' ? 'success' : ($state === 'failed' ? 'danger' : 'gray')),
            TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
        ]);
    }

    public static function configureCustomCuisine(Table $table): Table
    {
        return self::base($table)->columns([
            TextColumn::make('id')->label('编号')->sortable()->copyable(),
            TextColumn::make('title')->label('菜谱标题')->searchable()->wrap()->placeholder('—')->limit(32)->tooltip(fn (?string $state): ?string => $state),
            TextColumn::make('taste')
                ->label('口味要求')
                ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'preferences.taste', '—')),
            TextColumn::make('cuisine')
                ->label('菜系')
                ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->result_payload, 'cuisine', '—')),
            TextColumn::make('ingredient_count')
                ->label('食材数')
                ->state(fn (FeatureDataRecord $record): int => count(data_get($record->result_payload, 'ingredients', []))),
            TextColumn::make('status')->label('状态')->badge()->color(fn (?string $state): string => $state === 'success' ? 'success' : ($state === 'failed' ? 'danger' : 'gray')),
            TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
        ]);
    }

    private static function base(Table $table): Table
    {
        return $table
            ->deferFilters(false)
            ->defaultSort('id', 'desc')
            ->filters([
                Filter::make('keyword')
                    ->label('关键词')
                    ->schema([
                        TextInput::make('value')->label('标题关键词/编号'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $v = trim((string) ($data['value'] ?? ''));
                        if ($v === '') {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($v): void {
                            $q->where('title', 'like', '%'.$v.'%');
                            if (is_numeric($v)) {
                                $q->orWhere('id', (int) $v);
                            }
                        });
                    }),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'success' => '成功',
                        'failed' => '失败',
                    ]),
                Filter::make('created_between')
                    ->label('创建时间')
                    ->schema([
                        DatePicker::make('from')->label('开始日期'),
                        DatePicker::make('until')->label('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()->label('查看')->modalWidth('6xl'),
                DeleteAction::make()->label('删除'),
            ]);
    }
}
