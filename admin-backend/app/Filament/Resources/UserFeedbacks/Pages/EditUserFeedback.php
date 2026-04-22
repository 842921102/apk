<?php

namespace App\Filament\Resources\UserFeedbacks\Pages;

use App\Filament\Resources\UserFeedbacks\UserFeedbackResource;
use Filament\Resources\Pages\EditRecord;

class EditUserFeedback extends EditRecord
{
    protected static string $resource = UserFeedbackResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = (string) ($data['status'] ?? 'pending');
        if (in_array($status, ['resolved', 'rejected'], true) && empty($data['handled_at'])) {
            $data['handled_at'] = now();
        }

        return $data;
    }
}
