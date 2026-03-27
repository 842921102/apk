<?php

namespace App\Policies;

use App\Models\CirclePost;
use App\Models\User;
use App\Support\AppRole;

class CirclePostPolicy
{
    public function viewAny(User $user): bool
    {
        return AppRole::canAccessAdmin($user->role);
    }

    public function view(User $user, CirclePost $post): bool
    {
        return AppRole::canAccessAdmin($user->role);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CirclePost $post): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }

    public function delete(User $user, CirclePost $post): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }

    public function deleteAny(User $user): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }
}
