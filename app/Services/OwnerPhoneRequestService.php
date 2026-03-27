<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\OwnerPhoneRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OwnerPhoneRequestService
{
    public function createRequest(int $propertyId, int $requesterId, ?string $reason = null): OwnerPhoneRequest
    {
        $property = Property::query()->findOrFail($propertyId);

        if ((int) $property->created_by === $requesterId) {
            throw ValidationException::withMessages([
                'property_id' => ['Bạn không thể gửi yêu cầu xem SĐT cho chính bất động sản của mình.'],
            ]);
        }

        if (OwnerPhoneRequest::hasPendingRequest($propertyId, $requesterId)) {
            throw ValidationException::withMessages([
                'property_id' => ['Bạn đã có yêu cầu đang chờ duyệt cho BĐS này.'],
            ]);
        }

        return DB::transaction(function () use ($propertyId, $requesterId, $reason): OwnerPhoneRequest {
            $request = OwnerPhoneRequest::create([
                'property_id' => $propertyId,
                'requester_id' => $requesterId,
                'status' => 'PENDING',
                'reason' => $reason,
            ]);

            AuditLog::log('create_phone_request', OwnerPhoneRequest::class, $request->id);

            return $request;
        });
    }

    public function approve(OwnerPhoneRequest $request, User $admin, ?string $note = null): void
    {
        if ($request->status !== 'PENDING') {
            throw ValidationException::withMessages([
                'status' => ['Request is not pending.'],
            ]);
        }

        DB::transaction(function () use ($request, $admin, $note): void {
            $request->update([
                'status' => 'APPROVED',
                'admin_note' => $note,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            AuditLog::log('approve_phone_request', OwnerPhoneRequest::class, $request->id);
        });
    }

    public function reject(OwnerPhoneRequest $request, User $admin, string $reason): void
    {
        if ($request->status !== 'PENDING') {
            throw ValidationException::withMessages([
                'status' => ['Request is not pending.'],
            ]);
        }

        DB::transaction(function () use ($request, $admin, $reason): void {
            $request->update([
                'status' => 'REJECTED',
                'admin_note' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            AuditLog::log('reject_phone_request', OwnerPhoneRequest::class, $request->id, [
                'reason' => $reason
            ]);
        });
    }
}
