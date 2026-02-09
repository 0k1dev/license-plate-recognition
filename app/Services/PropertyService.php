<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropertyService
{
    public function approve(Property $property, User $admin, ?string $note = null): void
    {
        if ($property->approval_status !== 'PENDING') {
            throw ValidationException::withMessages([
                'approval_status' => ['Property is not pending approval.'],
            ]);
        }

        DB::transaction(function () use ($property, $admin, $note): void {
            $property->update([
                'approval_status' => 'APPROVED',
                'approval_note' => $note,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            AuditLog::log('approve_property', Property::class, $property->id, [
                'note' => $note,
            ]);
        });
    }

    public function reject(Property $property, User $admin, string $reason): void
    {
        if ($property->approval_status !== 'PENDING') {
            throw ValidationException::withMessages([
                'approval_status' => ['Property is not pending approval.'],
            ]);
        }

        DB::transaction(function () use ($property, $admin, $reason): void {
            $property->update([
                'approval_status' => 'REJECTED',
                'approval_note' => $reason,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            AuditLog::log('reject_property', Property::class, $property->id, [
                'reason' => $reason,
            ]);
        });
    }
}
