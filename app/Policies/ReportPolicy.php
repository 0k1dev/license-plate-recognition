<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOfficeAdmin();
    }

    public function view(User $user, Report $report): bool
    {
        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return true;
        }

        return $user->id === $report->reporter_id;
    }

    public function create(User $user): bool
    {
        return !$user->is_locked;
    }

    public function resolve(User $user): bool
    {
        return $user->isOfficeAdmin() || $user->isSuperAdmin();
    }
}
