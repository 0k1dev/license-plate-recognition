<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\File;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropertyService
{
    /**
     * Tạo property mới (auto PENDING) + attach files.
     */
    public function create(User $user, array $data): Property
    {
        $data['approval_status'] = 'PENDING';
        $data['created_by'] = $user->id;

        return DB::transaction(function () use ($data): Property {
            $property = Property::create($data);

            $this->attachFiles($property, $data);

            AuditLog::log('create_property', Property::class, $property->id);

            return $property;
        });
    }

    /**
     * Cập nhật property + re-attach files nếu có.
     */
    public function update(Property $property, array $data): Property
    {
        $allowedFields = [
            'title',
            'description',
            'address',
            'price',
            'area',
            'owner_name',
            'owner_phone',
            'bedrooms',
            'bathrooms',
            'direction',
            'floor',
            'lat',
            'lng',
            'legal_status',
            'category_id',
            'area_id',
            'district_id',
            'ward_id',
            'project_id',
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        DB::transaction(function () use ($property, $updateData, $data): void {
            $property->update($updateData);

            // Re-attach image files
            if (isset($data['image_file_ids'])) {
                File::where('owner_type', Property::class)
                    ->where('owner_id', $property->id)
                    ->where('purpose', 'PROPERTY_IMAGE')
                    ->update(['owner_type' => null, 'owner_id' => null]);

                File::whereIn('id', $data['image_file_ids'])
                    ->update([
                        'owner_type' => Property::class,
                        'owner_id' => $property->id,
                        'purpose' => 'PROPERTY_IMAGE',
                    ]);
            }

            // Re-attach legal doc files
            if (isset($data['legal_doc_file_ids'])) {
                File::where('owner_type', Property::class)
                    ->where('owner_id', $property->id)
                    ->where('purpose', 'LEGAL_DOC')
                    ->update(['owner_type' => null, 'owner_id' => null]);

                File::whereIn('id', $data['legal_doc_file_ids'])
                    ->update([
                        'owner_type' => Property::class,
                        'owner_id' => $property->id,
                        'purpose' => 'LEGAL_DOC',
                        'visibility' => 'PRIVATE',
                    ]);
            }

            AuditLog::log('update_property', Property::class, $property->id);
        });

        return $property->fresh();
    }

    /**
     * Approve property.
     */
    public function approve(Property $property, User $admin, ?string $note = null): void
    {
        if ($property->approval_status !== 'PENDING') {
            throw ValidationException::withMessages([
                'approval_status' => ['Property is not pending approval.'],
            ]);
        }

        $property->approve($admin, $note);
    }

    /**
     * Reject property.
     */
    public function reject(Property $property, User $admin, string $reason): void
    {
        if ($property->approval_status !== 'PENDING') {
            throw ValidationException::withMessages([
                'approval_status' => ['Property is not pending approval.'],
            ]);
        }

        $property->reject($admin, $reason);
    }

    /**
     * Attach files (images + legal docs) cho property.
     */
    private function attachFiles(Property $property, array $data): void
    {
        if (!empty($data['image_file_ids'])) {
            File::whereIn('id', $data['image_file_ids'])
                ->update([
                    'owner_type' => Property::class,
                    'owner_id' => $property->id,
                    'purpose' => 'PROPERTY_IMAGE',
                ]);
        }

        if (!empty($data['legal_doc_file_ids'])) {
            File::whereIn('id', $data['legal_doc_file_ids'])
                ->update([
                    'owner_type' => Property::class,
                    'owner_id' => $property->id,
                    'purpose' => 'LEGAL_DOC',
                    'visibility' => 'PRIVATE',
                ]);
        }
    }
}
