<?php

namespace App\Support;

use App\Models\AdminActionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class AdminActionLogger
{
    public static function record(string $action, Model $target, array $meta = []): void
    {
        $admin = Auth::user();
        if ($admin === null) {
            return;
        }

        AdminActionLog::query()->create([
            'admin_user_id' => $admin->getKey(),
            'action' => $action,
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'meta' => $meta !== [] ? $meta : null,
        ]);
    }
}
