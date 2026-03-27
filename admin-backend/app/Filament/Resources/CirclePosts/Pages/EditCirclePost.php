<?php

namespace App\Filament\Resources\CirclePosts\Pages;

use App\Filament\Resources\CirclePosts\CirclePostResource;
use App\Models\CirclePost;
use App\Support\AdminActionLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCirclePost extends EditRecord
{
    protected static string $resource = CirclePostResource::class;

    /**
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $imgs = $data['images'] ?? [];
        if (! is_array($imgs) || $imgs === []) {
            $data['images'] = [];

            return $data;
        }
        if (is_array($imgs[0] ?? null) && array_key_exists('url', $imgs[0])) {
            return $data;
        }
        if (isset($imgs[0]) && is_string($imgs[0])) {
            $data['images'] = array_values(array_filter(array_map(
                static fn (?string $u): array => ['url' => (string) $u],
                $imgs,
            ), static fn (array $row): bool => ($row['url'] ?? '') !== ''));
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $rows = $data['images'] ?? [];
        $urls = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row) && ! empty($row['url'])) {
                    $urls[] = (string) $row['url'];
                }
            }
        }
        $data['images'] = array_values(array_filter($urls, static fn (string $u): bool => $u !== ''));

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('软删除')
                ->visible(fn (): bool => CirclePostResource::canDelete($this->getRecord()))
                ->before(function (): void {
                    $record = $this->getRecord();
                    if (! $record instanceof CirclePost) {
                        return;
                    }
                    AdminActionLogger::record('circle_post.soft_deleted', $record, [
                        'user_id' => $record->user_id,
                    ]);
                }),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        if ($record instanceof CirclePost) {
            AdminActionLogger::record('circle_post.updated', $record, [
                'status' => $record->status,
            ]);
        }
    }
}
