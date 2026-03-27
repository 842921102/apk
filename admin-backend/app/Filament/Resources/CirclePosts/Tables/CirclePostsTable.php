<?php

namespace App\Filament\Resources\CirclePosts\Tables;

use App\Models\CirclePost;
use App\Support\AdminActionLogger;
use App\Support\CirclePostStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CirclePostsTable
{
    public static function configure(Table $table): Table
    {
        $statusLabels = CirclePostStatus::labels();

        return $table
            ->deferFilters(false)
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->copyable(),
                ImageColumn::make('cover')
                    ->label('封面')
                    ->getStateUsing(fn (CirclePost $record): ?string => $record->firstImageUrl())
                    ->square()
                    ->imageSize(48)
                    ->defaultImageUrl('data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#e5e7eb"><rect width="48" height="48" rx="6"/></svg>')),
                TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->placeholder('（无标题）')
                    ->limit(24)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->wrap(),
                TextColumn::make('content')
                    ->label('正文摘要')
                    ->formatStateUsing(fn (?string $state): string => mb_strimwidth((string) ($state ?? ''), 0, 120, '…'))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('用户昵称')
                    ->searchable()
                    ->limit(12)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('topic')
                    ->label('话题')
                    ->placeholder('—')
                    ->badge()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('source_type')
                    ->label('内容类型')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ((string) $state) {
                        'ai_generated' => 'AI生成',
                        'user_uploaded' => '用户实拍',
                        default => (string) $state,
                    }),
                TextColumn::make('like_count')
                    ->label('点赞')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('comment_count')
                    ->label('评论')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                IconColumn::make('related_product_id')
                    ->label('挂商品')
                    ->boolean()
                    ->getStateUsing(fn (CirclePost $record): bool => filled($record->related_product_id)),
                TextColumn::make('status')
                    ->label('状态')
                    ->formatStateUsing(fn (?string $state): string => $stateLabels[(string) $state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => match ((string) $state) {
                        CirclePostStatus::Normal => 'success',
                        CirclePostStatus::Offline => 'warning',
                        CirclePostStatus::Deleted => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('is_recommended')
                    ->label('推荐')
                    ->boolean(),
                IconColumn::make('is_pinned')
                    ->label('置顶')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('发布时间')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Filter::make('keyword')
                    ->label('关键词')
                    ->schema([
                        TextInput::make('q')
                            ->label('标题 / 正文 / 昵称')
                            ->placeholder('模糊搜索'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $q = isset($data['q']) ? trim((string) $data['q']) : '';
                        if ($q === '') {
                            return $query;
                        }
                        $like = '%'.$q.'%';

                        return $query->where(function (Builder $sub) use ($like): void {
                            $sub->where('title', 'like', $like)
                                ->orWhere('content', 'like', $like)
                                ->orWhere('topic', 'like', $like)
                                ->orWhereHas('user', fn (Builder $uq) => $uq->where('name', 'like', $like));
                        });
                    }),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options($statusLabels),
                SelectFilter::make('topic')
                    ->label('话题')
                    ->options(fn (): array => CirclePost::query()
                        ->whereNotNull('topic')
                        ->where('topic', '!=', '')
                        ->distinct()
                        ->orderBy('topic')
                        ->pluck('topic', 'topic')
                        ->all()),
                TernaryFilter::make('is_recommended')
                    ->label('推荐'),
                TernaryFilter::make('is_pinned')
                    ->label('置顶'),
                Filter::make('published_at')
                    ->label('发布时间')
                    ->schema([
                        DatePicker::make('from')->label('从'),
                        DatePicker::make('until')->label('至'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $q) => $q->whereDate('published_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $q) => $q->whereDate('published_at', '<=', $data['until']),
                            );
                    }),
                SelectFilter::make('trashed_scope')
                    ->label('删除视图')
                    ->options([
                        'active' => '未删除',
                        'only_trashed' => '仅已删除',
                        'with_trashed' => '含已删除（全部）',
                    ])
                    ->default('active')
                    ->query(function (Builder $query, array $data): Builder {
                        $v = (string) ($data['trashed_scope'] ?? 'active');

                        return match ($v) {
                            'only_trashed' => $query->onlyTrashed(),
                            'with_trashed' => $query->withTrashed(),
                            default => $query->whereNull('circle_posts.deleted_at'),
                        };
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()
                    ->label('详情')
                    ->slideOver()
                    ->modalWidth('6xl'),
                EditAction::make()
                    ->label('编辑')
                    ->visible(fn (CirclePost $record): bool => auth()->user()?->can('update', $record) ?? false),
                Action::make('offline')
                    ->label('下架')
                    ->icon(Heroicon::OutlinedEyeSlash)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(function (CirclePost $record): bool {
                        return ($record->status === CirclePostStatus::Normal)
                            && ! $record->trashed()
                            && (auth()->user()?->can('update', $record) ?? false);
                    })
                    ->action(function (CirclePost $record): void {
                        $record->status = CirclePostStatus::Offline;
                        $record->save();
                        AdminActionLogger::record('circle_post.offline', $record, []);
                    }),
                Action::make('online')
                    ->label('上架')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (CirclePost $record): bool {
                        return ($record->status === CirclePostStatus::Offline)
                            && ! $record->trashed()
                            && (auth()->user()?->can('update', $record) ?? false);
                    })
                    ->action(function (CirclePost $record): void {
                        $record->status = CirclePostStatus::Normal;
                        if ($record->published_at === null) {
                            $record->published_at = now();
                        }
                        $record->save();
                        AdminActionLogger::record('circle_post.online', $record, []);
                    }),
                Action::make('markDeleted')
                    ->label('标记删除')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('将状态标为已删除并软删除，前台不可见。')
                    ->visible(function (CirclePost $record): bool {
                        return ! $record->trashed()
                            && (auth()->user()?->can('delete', $record) ?? false);
                    })
                    ->action(function (CirclePost $record): void {
                        $record->status = CirclePostStatus::Deleted;
                        $record->save();
                        $record->delete();
                        AdminActionLogger::record('circle_post.mark_deleted', $record, []);
                    }),
                Action::make('restorePost')
                    ->label('恢复帖子')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('gray')
                    ->visible(fn (CirclePost $record): bool => $record->trashed() && (auth()->user()?->can('update', $record) ?? false))
                    ->action(function (CirclePost $record): void {
                        $record->restore();
                        if ($record->status === CirclePostStatus::Deleted) {
                            $record->status = CirclePostStatus::Offline;
                            $record->save();
                        }
                        AdminActionLogger::record('circle_post.restored', $record, []);
                    }),
                Action::make('toggleRecommend')
                    ->label(fn (CirclePost $record): string => $record->is_recommended ? '取消推荐' : '设为推荐')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->visible(fn (CirclePost $record): bool => ! $record->trashed() && (auth()->user()?->can('update', $record) ?? false))
                    ->action(function (CirclePost $record): void {
                        $record->is_recommended = ! $record->is_recommended;
                        $record->save();
                        AdminActionLogger::record('circle_post.toggle_recommend', $record, ['is_recommended' => $record->is_recommended]);
                    }),
                Action::make('togglePin')
                    ->label(fn (CirclePost $record): string => $record->is_pinned ? '取消置顶' : '设为置顶')
                    ->icon(Heroicon::OutlinedArrowUpCircle)
                    ->visible(fn (CirclePost $record): bool => ! $record->trashed() && (auth()->user()?->can('update', $record) ?? false))
                    ->action(function (CirclePost $record): void {
                        $record->is_pinned = ! $record->is_pinned;
                        $record->save();
                        AdminActionLogger::record('circle_post.toggle_pin', $record, ['is_pinned' => $record->is_pinned]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('批量软删除')
                        ->visible(fn (): bool => auth()->user()?->can('deleteAny', CirclePost::class) ?? false)
                        ->before(function (Collection $records): void {
                            foreach ($records as $record) {
                                if (! $record instanceof CirclePost) {
                                    continue;
                                }
                                $record->status = CirclePostStatus::Deleted;
                                $record->save();
                            }
                        }),
                    RestoreBulkAction::make()
                        ->label('批量恢复'),
                ]),
            ]);
    }
}
