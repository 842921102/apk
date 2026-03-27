<?php

namespace App\Filament\Resources\CirclePosts\Schemas;

use App\Models\CirclePost;
use App\Support\CircleCommentStatus;
use App\Support\CirclePostStatus;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CirclePostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基础信息')
                    ->schema([
                        TextEntry::make('id')->label('ID')->copyable(),
                        TextEntry::make('user.name')->label('发帖用户'),
                        TextEntry::make('user_id')->label('用户 ID')->copyable(),
                        TextEntry::make('status')
                            ->label('状态')
                            ->formatStateUsing(fn (?string $state): string => CirclePostStatus::labels()[(string) $state] ?? (string) $state)
                            ->badge()
                            ->color(fn (?string $state): string => match ((string) $state) {
                                CirclePostStatus::Normal => 'success',
                                CirclePostStatus::Offline => 'warning',
                                CirclePostStatus::Deleted => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('published_at')->label('发布时间')->dateTime()->placeholder('—'),
                        TextEntry::make('created_at')->label('创建时间')->dateTime(),
                        TextEntry::make('updated_at')->label('更新时间')->dateTime(),
                        TextEntry::make('deleted_at')->label('软删除时间')->dateTime()->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('运营标记')
                    ->schema([
                        TextEntry::make('is_recommended')->label('推荐')->formatStateUsing(fn (?bool $s): string => $s ? '是' : '否')->badge(),
                        TextEntry::make('is_pinned')->label('置顶')->formatStateUsing(fn (?bool $s): string => $s ? '是' : '否')->badge(),
                        TextEntry::make('like_count')->label('点赞数'),
                        TextEntry::make('comment_count')->label('评论数'),
                        TextEntry::make('favorite_count')->label('收藏数'),
                        TextEntry::make('source_type')
                            ->label('内容类型')
                            ->formatStateUsing(fn (?string $state): string => match ((string) $state) {
                                'ai_generated' => 'AI生成',
                                'user_uploaded' => '用户实拍',
                                default => (string) $state,
                            }),
                        TextEntry::make('publish_source')
                            ->label('发布来源')
                            ->formatStateUsing(fn (?string $state): string => match ((string) $state) {
                                'ai_result' => 'AI结果页',
                                'manual_upload' => '手动上传',
                                default => (string) $state,
                            }),
                        TextEntry::make('related_product_id')->label('关联商品ID')->placeholder('未绑定'),
                        TextEntry::make('topic')->label('话题'),
                    ])
                    ->columns(2),
                Section::make('正文')
                    ->schema([
                        TextEntry::make('title')->label('标题')->placeholder('（无标题）'),
                        TextEntry::make('description')->label('描述')->placeholder('（无描述）')->columnSpanFull(),
                        TextEntry::make('content')
                            ->label('内容')
                            ->columnSpanFull()
                            ->prose(),
                    ]),
                Section::make('封面与图片')
                    ->schema([
                        ImageEntry::make('cover')
                            ->label('封面（首张）')
                            ->getStateUsing(fn (CirclePost $record): ?string => $record->firstImageUrl())
                            ->placeholder('无')
                            ->columnSpanFull(),
                        TextEntry::make('images')
                            ->label('全部图片 URL')
                            ->formatStateUsing(function ($state): string {
                                if (! is_array($state) || $state === []) {
                                    return '无';
                                }

                                return implode("\n", array_values(array_filter($state, fn ($u) => is_string($u) && $u !== '')));
                            })
                            ->columnSpanFull()
                            ->copyable(),
                    ]),
                Section::make('最新评论（前 50 条）')
                    ->schema([
                        TextEntry::make('comments_block')
                            ->label('')
                            ->columnSpanFull()
                            ->html()
                            ->state(function (CirclePost $record): string {
                                $lines = $record->comments()
                                    ->with('user')
                                    ->orderByDesc('id')
                                    ->limit(50)
                                    ->get()
                                    ->map(function ($c): string {
                                        $st = (string) $c->status;
                                        $stLabel = CircleCommentStatus::labels()[$st] ?? $st;
                                        $name = e((string) ($c->user?->name ?? ''));
                                        $body = e(mb_strimwidth((string) $c->content, 0, 400, '…'));

                                        return '<div style="margin-bottom:12px;border-bottom:1px solid #eee;padding-bottom:8px"><strong>#'.$c->id.'</strong> '.$name.' · <span style="color:#6b7280">'.$c->created_at?->format('Y-m-d H:i').'</span> · '.$stLabel.'<br/>'.$body.'</div>';
                                    });

                                return $lines->isEmpty()
                                    ? '<p style="color:#6b7280">暂无评论</p>'
                                    : $lines->implode('');
                            }),
                    ])
                    ->collapsible(),
            ]);
    }
}
