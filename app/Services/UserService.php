<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class UserService
{
    /**
     * Khóa tài khoản người dùng
     */
    public function lock(User $user, string $reason): void
    {
        $user->update([
            'is_locked' => true,
            'locked_at' => now(),
            'lock_reason' => $reason,
        ]);

        AuditLog::log('lock_user', User::class, $user->id, [
            'reason' => $reason,
        ]);
    }

    /**
     * Mở khóa tài khoản người dùng
     */
    public function unlock(User $user): void
    {
        $user->update([
            'is_locked' => false,
            'locked_at' => null,
            'lock_reason' => null,
        ]);

        AuditLog::log('unlock_user', User::class, $user->id);
    }

    /**
     * Cập nhật thông tin user
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user->fresh();
    }

    /**
     * Xác thực email
     */
    public function verifyEmail(User $user): void
    {
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}
