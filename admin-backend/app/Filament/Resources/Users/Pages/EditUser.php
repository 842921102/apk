<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AdminActionLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => UserResource::canDelete($this->getRecord()))
                ->before(function (): void {
                    $record = $this->getRecord();
                    if (! $record instanceof User) {
                        return;
                    }

                    AdminActionLogger::record('user.deleted', $record, [
                        'email' => $record->email,
                        'role' => $record->role,
                    ]);
                }),
        ];
    }

    protected function afterSave(): void
    {
        AdminActionLogger::record('user.updated', $this->getRecord(), [
            'role' => $this->getRecord()->role,
            'is_active' => $this->getRecord()->is_active,
        ]);
    }
}
