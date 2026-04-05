<?php

namespace App\Filament\Resources\AiModelConfigs\Tables;

use App\Models\AiModelConfig;
use App\Services\AiModelCenterService;
use App\Support\AdminActionLogger;
use App\Support\AiScene;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AiModelConfigsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferFilters(false)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('编号')->sortable()->copyable(),
                TextColumn::make('scene_code')
                    ->label('场景')
                    ->formatStateUsing(fn (?string $state): string => AiScene::options()[(string) $state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('provider.provider_name')->label('供应商')->searchable(),
                TextColumn::make('model.model_name')->label('模型')->searchable(),
                TextColumn::make('key_masked')->label('接口密钥')->placeholder('未配置'),
                TextColumn::make('is_enabled')
                    ->label('启用')
                    ->formatStateUsing(fn (?bool $state): string => $state ? '是' : '否')
                    ->badge(),
                TextColumn::make('is_default')
                    ->label('默认')
                    ->formatStateUsing(fn (?bool $state): string => $state ? '是' : '否')
                    ->badge(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('scene_code')
                    ->label('场景')
                    ->options(AiScene::options()),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()->label('查看')->modalWidth('5xl'),
                EditAction::make()->label('编辑'),
                Action::make('testConnection')
                    ->label('连接测试')
                    ->icon('heroicon-o-signal')
                    ->color('warning')
                    ->action(function (AiModelConfig $record): void {
                        $result = app(AiModelCenterService::class)->testConnection($record, (int) auth()->id());
                        AdminActionLogger::record('ai_model_config.test_connection', $record, [
                            'scene_code' => $record->scene_code,
                            'status' => $result['status'],
                            'request_url' => $result['request_url'],
                            'error_message' => $result['error_message'],
                        ]);

                        $body =
                            $result['status'] === 'success'
                                ? ($result['error_message'] ?? '测试完成')
                                : trim(
                                    ($result['error_message'] ?? '测试完成')
                                        .(isset($result['request_url']) ? "\n".$result['request_url'] : ''),
                                );

                        Notification::make()
                            ->title($result['status'] === 'success' ? '连接成功' : '连接失败')
                            ->body($body)
                            ->status($result['status'] === 'success' ? 'success' : 'danger')
                            ->send();
                    }),
                DeleteAction::make()->label('删除'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
