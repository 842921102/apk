<?php

namespace App\Filament\Resources\CircleComments\Tables;

use App\Models\CircleComment;
use App\Support\AdminActionLogger;
use App\Support\CircleCommentStatus;
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
use Illuminate\Support\Collection;

class CircleCommentsTable
{
    public static function configure(Table $table): Table
    {
        $statusLabels = CircleCommentStatus::labels();

        return $table
            ->deferFilters(false)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('评论 ID')
                    ->sortable()
                    ->copyable(),
                TextColumn::make('post_id')
                    ->label('帖子 ID')
                    ->sortable()
                    ->copyable(),
                TextColumn::make('post.title')
                    ->label('帖子标题')
                    ->placeholder('—')
                    ->limit(20)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('用户昵称')
                    ->searchable(),
                TextColumn::make('content')
                    ->label('内容')
                    ->formatStateUsing(fn (?string $state): string => mb_strimwidth((string) ($state ?? ''), 0, 80, '…'))
                    ->wrap(),
                TextColumn::make('status')
                    ->label('状态')
                    ->formatStateUsing(fn (?string $state): string => $statusLabels[(string) $state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => (string) $state === CircleCommentStatus::Normal ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->label('评论时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('keyword')
                    ->label('关键词')
                    ->schema([
                        TextInput::make('q')->label('内容 / 昵称 / 帖子标题'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $q = isset($data['q']) ? trim((string) $data['q']) : '';
                        if ($q === '') {
                            return $query;
                        }
                        $like = '%'.$q.'%';

                        return $query->where(function (Builder $sub) use ($like): void {
                            $sub->where('content', 'like', $like)
                                ->orWhereHas('user', fn (Builder $uq) => $uq->where('name', 'like', $like))
                                ->orWhereHas('post', fn (Builder $pq) => $pq->where('title', 'like', $like)->orWhere('content', 'like', $like));
                        });
                    }),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options($statusLabels),
                Filter::make('post_id')
                    ->label('帖子 ID')
                    ->schema([
                        TextInput::make('post_id')->numeric()->label('精确 ID'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['post_id'] ?? null),
                            fn (Builder $q): Builder => $q->where('post_id', (int) $data['post_id']),
                        );
                    }),
                Filter::make('created_at')
                    ->label('评论时间')
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
                    ->label('查看')
                    ->slideOver()
                    ->modalWidth('4xl'),
                DeleteAction::make()
                    ->label('删除')
                    ->visible(fn (CircleComment $record): bool => auth()->user()?->can('delete', $record) ?? false)
                    ->before(function (CircleComment $record): void {
                        $record->status = CircleCommentStatus::Deleted;
                        $record->save();
                        if ($record->post) {
                            $record->post->decrement('comment_count');
                            $record->post->update(['comment_count' => max(0, (int) $record->post->fresh()?->comment_count)]);
                        }
                        AdminActionLogger::record('circle_comment.deleted', $record, ['post_id' => $record->post_id]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('批量删除')
                        ->visible(fn (): bool => auth()->user()?->can('deleteAny', CircleComment::class) ?? false)
                        ->before(function (Collection $records): void {
                            foreach ($records as $record) {
                                if (! $record instanceof CircleComment) {
                                    continue;
                                }
                                $record->status = CircleCommentStatus::Deleted;
                                $record->save();
                                if ($record->post) {
                                    $record->post->decrement('comment_count');
                                }
                                AdminActionLogger::record('circle_comment.bulk_deleted', $record, ['post_id' => $record->post_id]);
                            }
                        }),
                ]),
            ]);
    }
}
