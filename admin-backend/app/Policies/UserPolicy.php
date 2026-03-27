<?php

namespace App\Policies;

use App\Models\User;
use App\Support\AppRole;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return AppRole::canAccessAdmin($user->role);
    }

    public function view(User $user, User $model): bool
    {
        return AppRole::canAccessAdmin($user->role);
    }

    public function create(User $user): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }

    public function update(User $user, User $model): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }

    public function delete(User $user, User $model): bool
    {
        return AppRole::normalize($user->role) === 'super_admin';
    }

    public function deleteAny(User $user): bool
    {
        return AppRole::normalize($user->role) === 'super_admin';
    }

    /** 列表/表单中切换启用、禁用 */
    public function toggleActive(User $user, User $model): bool
    {
        if ((int) $user->id === (int) $model->id) {
            return false;
        }

        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }

    /** 修改用户角色（含后台账号与普通微信用户） */
    public function changeRole(User $user, User $model): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }
}
