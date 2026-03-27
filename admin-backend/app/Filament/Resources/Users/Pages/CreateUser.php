<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\AdminActionLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['password'])) {
            throw ValidationException::withMessages([
                'password' => '请填写密码。',
            ]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        AdminActionLogger::record('user.created', $this->getRecord(), [
            'email' => $this->getRecord()->email,
            'role' => $this->getRecord()->role,
        ]);
    }
}
