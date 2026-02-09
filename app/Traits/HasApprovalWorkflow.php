<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ApprovalStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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
        $approver ??= auth()->user();

        $this->update([
            'approval_status' => ApprovalStatus::APPROVED->value,
            'approval_note' => $note,
            'approved_by' => $approver?->id,
            'approved_at' => now(),
        ]);

        AuditLog::log(
            action: 'approve_' . $this->getTable(),
            targetType: static::class,
            targetId: $this->id,
            meta: ['note' => $note]
        );

        return true;
    }

    /**
     * Từ chối record
     */
    public function reject(?User $approver = null, string $reason = ''): bool
    {
        $approver ??= auth()->user();

        $this->update([
            'approval_status' => ApprovalStatus::REJECTED->value,
            'approval_note' => $reason,
            'approved_by' => $approver?->id,
            'approved_at' => now(),
        ]);

        AuditLog::log(
            action: 'reject_' . $this->getTable(),
            targetType: static::class,
            targetId: $this->id,
            meta: ['reason' => $reason]
        );

        return true;
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
    public function scopePending($query)
    {
        return $query->where('approval_status', ApprovalStatus::PENDING->value);
    }

    /**
     * Scope: chỉ lấy records đã duyệt
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', ApprovalStatus::APPROVED->value);
    }
}
