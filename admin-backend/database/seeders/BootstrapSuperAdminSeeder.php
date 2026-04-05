<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class BootstrapSuperAdminSeeder extends Seeder
{
    /**
     * 首次可登录 Filament 的超级管理员；在 .env 配置邮箱与密码后执行：
     * php artisan db:seed --class=BootstrapSuperAdminSeeder
     */
    public function run(): void
    {
        $email = env('ADMIN_BOOTSTRAP_EMAIL');
        $password = env('ADMIN_BOOTSTRAP_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command?->warn('已跳过：请在 .env 中设置 ADMIN_BOOTSTRAP_EMAIL 与 ADMIN_BOOTSTRAP_PASSWORD。');

            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_BOOTSTRAP_NAME', '超级管理员'),
                'password' => $password,
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ],
        );
        $user->ensureProfile();

        $this->command?->info('超级管理员已就绪，邮箱：'.$email);
    }
}
