<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['order_status'] ?? null) === 'paid' && empty($this->record->paid_at)) {
            $data['paid_at'] = Carbon::now();
        }
        if (($data['order_status'] ?? null) === 'shipped' && empty($this->record->shipped_at)) {
            $data['shipped_at'] = Carbon::now();
        }
        if (($data['order_status'] ?? null) === 'completed' && empty($this->record->completed_at)) {
            $data['completed_at'] = Carbon::now();
        }
        if (($data['order_status'] ?? null) === 'cancelled' && empty($this->record->cancelled_at)) {
            $data['cancelled_at'] = Carbon::now();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ship')
                ->label('发货')
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->order_status, ['paid', 'shipped'], true))
                ->form([
                    TextInput::make('logistics_company')->label('物流公司')->required()->maxLength(64),
                    TextInput::make('logistics_no')->label('物流单号')->required()->maxLength(64),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'logistics_company' => $data['logistics_company'],
                        'logistics_no' => $data['logistics_no'],
                        'order_status' => 'shipped',
                        'shipped_at' => Carbon::now(),
                    ]);
                }),
            Action::make('mark_completed')
                ->label('标记完成')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->order_status, ['shipped', 'completed'], true))
                ->action(function (): void {
                    $this->record->update([
                        'order_status' => 'completed',
                        'completed_at' => Carbon::now(),
                    ]);
                }),
            Action::make('cancel_order')
                ->label('取消订单')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! in_array($this->record->order_status, ['completed', 'cancelled'], true))
                ->action(function (): void {
                    $this->record->update([
                        'order_status' => 'cancelled',
                        'cancelled_at' => Carbon::now(),
                    ]);
                }),
        ];
    }
}
