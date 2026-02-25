<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait để filter dữ liệu theo khu vực của user
 * Sử dụng cho các Resource và Widget cần scope theo area
 */
trait HasAreaScope
{
    /**
     * Apply area scope cho query dựa trên role của user
     */
    protected function applyAreaScope(Builder $query, ?User $user = null): Builder
    {
        /** @var \App\Models\User|null $user */
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0'); // No access
        }

        // Admin roles có full access
        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return $query;
        }

        // Field staff chỉ thấy properties trong area của mình
        // Field staff chỉ thấy properties trong area của mình
        if ($user->isFieldStaff()) {
            if (!empty($user->area_ids)) {
                return $query->whereIn('area_id', $user->area_ids);
            }
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Apply area scope cho query thông qua relationship
     * Dùng khi cần filter Post/File qua Property
     */
    protected function applyAreaScopeViaProperty(Builder $query, ?User $user = null): Builder
    {
        /** @var \App\Models\User|null $user */
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return $query;
        }

        if ($user->isFieldStaff()) {
            if (!empty($user->area_ids)) {
                return $query->whereHas('property', function (Builder $q) use ($user) {
                    $q->whereIn('area_id', $user->area_ids);
                });
            }
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Kiểm tra user có quyền truy cập area không
     */
    protected function userCanAccessArea(int $areaId, ?User $user = null): bool
    {
        /** @var \App\Models\User|null $user */
        $user ??= auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return true;
        }

        return $user->area_ids && in_array($areaId, $user->area_ids);
    }
}
