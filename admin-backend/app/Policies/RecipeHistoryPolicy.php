<?php

namespace App\Policies;

use App\Models\RecipeHistory;
use App\Models\User;
use App\Support\AppRole;

class RecipeHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return AppRole::canAccessAdmin($user->role);
    }

    public function view(User $user, RecipeHistory $history): bool
    {
        return AppRole::canAccessAdmin($user->role);
    }

    public function delete(User $user, RecipeHistory $history): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }

    public function deleteAny(User $user): bool
    {
        return in_array(AppRole::normalize($user->role), ['operator', 'super_admin'], true);
    }
}

