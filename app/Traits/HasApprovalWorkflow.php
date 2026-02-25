<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ApprovalStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Trait cho các model có workflow approval (Property, etc.)
 */
trait HasApprovalWorkflow
{
    /**
     * Duyệt record
     */
    public function approve(?User $approver = null, ?string $note = null): bool
    {
        /** @var \App\Models\User|null $approver */
        $approver ??= auth()->user();

        return DB::transaction(function () use ($approver, $note) {
            $updated = $this->update([
                'approval_status' => ApprovalStatus::APPROVED->value,
                'approval_note' => $note,
                'approved_by' => $approver?->id,
                'approved_at' => now(),
            ]);

            if ($updated) {
                AuditLog::log(
                    action: 'approve_' . $this->getTable(),
                    targetType: static::class,
                    targetId: $this->id,
                    payload: ['note' => $note]
                );
                return true;
            }
            return false;
        });
    }

    /**
     * Từ chối record
     */
    public function reject(?User $approver = null, string $reason = ''): bool
    {
        /** @var \App\Models\User|null $approver */
        $approver ??= auth()->user();

        return DB::transaction(function () use ($approver, $reason) {
            $updated = $this->update([
                'approval_status' => ApprovalStatus::REJECTED->value,
                'approval_note' => $reason,
                'approved_by' => $approver?->id,
                'approved_at' => now(),
            ]);

            if ($updated) {
                AuditLog::log(
                    action: 'reject_' . $this->getTable(),
                    targetType: static::class,
                    targetId: $this->id,
                    payload: ['reason' => $reason]
                );
                return true;
            }
            return false;
        });
    }

    /**
     * Kiểm tra có đang pending không
     */
    public function isPending(): bool
    {
        return $this->approval_status === ApprovalStatus::PENDING->value
            || $this->approval_status === ApprovalStatus::PENDING;
    }

    /**
     * Kiểm tra đã được duyệt chưa
     */
    public function isApproved(): bool
    {
        return $this->approval_status === ApprovalStatus::APPROVED->value
            || $this->approval_status === ApprovalStatus::APPROVED;
    }

    /**
     * Kiểm tra đã bị từ chối chưa
     */
    public function isRejected(): bool
    {
        return $this->approval_status === ApprovalStatus::REJECTED->value
            || $this->approval_status === ApprovalStatus::REJECTED;
    }

    /**
     * Scope: chỉ lấy records pending
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', ApprovalStatus::PENDING->value);
    }

    /**
     * Scope: chỉ lấy records đã duyệt
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', ApprovalStatus::APPROVED->value);
    }
}
