<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOfficeAdmin();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isOfficeAdmin()) {
            return !$model->isSuperAdmin();
        }

        return $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isOfficeAdmin()) {
            // OfficeAdmin không được sửa SuperAdmin hoặc OfficeAdmin khác (tùy nghiệp vụ)
            return !$model->isSuperAdmin() && !$model->isOfficeAdmin();
        }

        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    // Custom actions
    public function lock(User $user, User $model): bool
    {
        if ($user->id === $model->id) return false; // Không tự khóa mình

        if ($user->isSuperAdmin()) return true;

        if ($user->isOfficeAdmin()) {
            return $model->isFieldStaff(); // Chỉ khóa nhân viên hiện trường
        }

        return false;
    }
}
