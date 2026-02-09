<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OwnerPhoneRequest;
use App\Models\User;

class OwnerPhoneRequestPolicy
{
    public function viewAny(User $user): bool
    {
        // Chỉ admin hoặc người tạo mới xem được list requests
        return $user->isSuperAdmin() || $user->isOfficeAdmin();
    }

    public function view(User $user, OwnerPhoneRequest $request): bool
    {
        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return true;
        }

        return $user->id === $request->requester_id;
    }

    public function create(User $user): bool
    {
        return $user->isFieldStaff() && !$user->is_locked;
    }

    public function approve(User $user): bool
    {
        return $user->isOfficeAdmin() || $user->isSuperAdmin();
    }
}
