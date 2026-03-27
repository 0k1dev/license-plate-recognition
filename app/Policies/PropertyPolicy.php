<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OwnerPhoneRequest;
use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_property');
    }

    public function view(User $user, Property $property): bool
    {
        if (!$user->can('view_property')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isFieldStaff()) {
            // Allow if creator
            if ($property->created_by === $user->id) {
                return true;
            }
            // Allow if in area
            return in_array($property->area_id, $user->area_ids ?? [])
                && $property->approval_status === 'APPROVED';
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create_property');
    }

    public function update(User $user, Property $property): bool
    {
        if (!$user->can('update_property')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $property->created_by === $user->id;
    }

    public function delete(User $user, Property $property): bool
    {
        if (!$user->can('delete_property')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $property->created_by === $user->id;
    }

    public function approve(User $user): bool
    {
        return $user->isAdmin() || $user->can('approve_property');
    }

    // Masking checks
    public function viewOwnerPhone(User $user, Property $property): bool
    {
        // Admin luôn xem được
        if ($user->isAdmin()) {
            return true;
        }

        // Người tạo xem được
        if ($property->created_by === $user->id) {
            return true;
        }

        // FIELD_STAFF: phải có request APPROVED
        if ($property->relationLoaded('myApprovedPhoneRequest')) {
            return (bool) $property->myApprovedPhoneRequest;
        }

        return OwnerPhoneRequest::where('property_id', $property->id)
            ->where('requester_id', $user->id)
            ->where('status', 'APPROVED')
            ->exists();
    }

    public function viewLegalDocs(User $user, Property $property): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $property->created_by === $user->id;
    }
}
