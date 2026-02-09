<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOfficeAdmin();
    }

    public function view(User $user, File $file): bool
    {
        if ($file->visibility === 'PUBLIC') {
            return true;
        }

        // PRIVATE file logic
        // Admin xem hết
        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return true;
        }

        // Người upload xem được
        if ($file->uploaded_by === $user->id) {
            return true;
        }

        // Check owner polymorphic
        // Ví dụ: nếu file thuộc Property, check quyền viewLegalDocs của Property
        if ($file->owner_type === 'App\Models\Property') {
            $property = $file->owner;
            if ($property) {
                // Reuse logic from PropertyPolicy
                // Ở đây ta gọi policy của Property
                return $user->can('viewLegalDocs', $property);
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return !$user->is_locked;
    }

    public function delete(User $user, File $file): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $file->uploaded_by === $user->id;
    }

    public function update(User $user, ?File $file = null): bool
    {
        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return true;
        }

        if ($file instanceof File) {
            return $file->uploaded_by === $user->id;
        }

        return !$user->is_locked;
    }
}
