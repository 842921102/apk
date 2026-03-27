<?php

namespace App\Policies;

use App\Models\AiModelConfig;
use App\Models\User;
use App\Support\AppRole;

class AiModelConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return AppRole::canAccessAdmin($user->role);
    }

    public function view(User $user, AiModelConfig $config): bool
    {
        return AppRole::canAccessAdmin($user->role);
    }

    public function create(User $user): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }

    public function update(User $user, AiModelConfig $config): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }

    public function delete(User $user, AiModelConfig $config): bool
    {
        return AppRole::normalize($user->role) === 'super_admin';
    }

    public function deleteAny(User $user): bool
    {
        return AppRole::normalize($user->role) === 'super_admin';
    }
}

