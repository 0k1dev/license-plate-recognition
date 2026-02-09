<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->isSuperAdmin();
    }
}
