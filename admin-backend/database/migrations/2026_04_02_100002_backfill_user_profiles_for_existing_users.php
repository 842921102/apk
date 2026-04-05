<?php

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->select('id')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                UserProfile::query()->firstOrCreate(
                    ['user_id' => $user->id],
                    ['gender' => 'unknown'],
                );
            }
        });
    }

    public function down(): void
    {
        // 保留表结构；不回删资料行，避免误伤
    }
};
