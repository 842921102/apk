<?php

namespace App\Filament\Resources\EatMemeRecords\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EatMemeRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('基础')
                ->schema([
                    TextEntry::make('id')->label('ID'),
                    TextEntry::make('channel')->label('渠道'),
                    TextEntry::make('status')->label('状态')->badge(),
                    TextEntry::make('result_title')->label('推荐标题')->placeholder('—'),
                    TextEntry::make('result_cuisine')->label('菜系')->placeholder('—'),
                    TextEntry::make('taste')->label('口味偏好')->placeholder('—'),
                    TextEntry::make('avoid')->label('忌口')->placeholder('—'),
                    TextEntry::make('people')->label('人数')->placeholder('—'),
                    TextEntry::make('error_message')->label('错误信息')->placeholder('—'),
                    TextEntry::make('source_ip')->label('来源 IP')->placeholder('—'),
                    TextEntry::make('requested_at')->label('请求时间')->dateTime()->placeholder('—'),
                    TextEntry::make('created_at')->label('创建时间')->dateTime(),
                ])->columns(2),
            Section::make('内容')
                ->schema([
                    TextEntry::make('result_ingredients')->label('食材(JSON)')->placeholder('—'),
                    TextEntry::make('result_content')->label('推荐正文')->placeholder('—'),
                ]),
        ]);
    }
}

