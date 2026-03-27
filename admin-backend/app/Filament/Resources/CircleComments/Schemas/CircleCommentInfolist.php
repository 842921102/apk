<?php

namespace App\Filament\Resources\CircleComments\Schemas;

use App\Support\CircleCommentStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CircleCommentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('评论信息')
                    ->schema([
                        TextEntry::make('id')->label('评论 ID')->copyable(),
                        TextEntry::make('post_id')->label('帖子 ID')->copyable(),
                        TextEntry::make('post.title')
                            ->label('帖子标题')
                            ->placeholder('（无标题）'),
                        TextEntry::make('user.name')->label('用户昵称'),
                        TextEntry::make('user_id')->label('用户 ID')->copyable(),
                        TextEntry::make('status')
                            ->label('状态')
                            ->formatStateUsing(fn (?string $state): string => CircleCommentStatus::labels()[(string) $state] ?? (string) $state)
                            ->badge(),
                        TextEntry::make('created_at')->label('评论时间')->dateTime(),
                        TextEntry::make('content')
                            ->label('内容')
                            ->columnSpanFull()
                            ->prose(),
                    ])
                    ->columns(2),
            ]);
    }
}
