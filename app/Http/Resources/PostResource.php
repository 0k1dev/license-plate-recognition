<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User|null */
        $user = $request->user();
        $canViewOwnerPhone = false;
        $ownerPhoneRequestStatus = 'NONE';
        $canRequestOwnerPhone = false;

        if ($this->relationLoaded('property') && $this->property) {
            $property = $this->property;

            $isAdmin = $user && $user->isAdmin();
            $isCreator = $user && (int) $property->created_by === (int) $user->id;
            $isApproved = $property->relationLoaded('myApprovedPhoneRequest') && (bool) $property->myApprovedPhoneRequest;

            $canViewOwnerPhone = (bool) ($isAdmin || $isCreator || $isApproved);

            if ($isAdmin || $isCreator) {
                $ownerPhoneRequestStatus = 'NOT_REQUIRED';
            } elseif ($isApproved) {
                $ownerPhoneRequestStatus = 'APPROVED';
            } elseif ($property->relationLoaded('myLatestPhoneRequest') && $property->myLatestPhoneRequest) {
                $ownerPhoneRequestStatus = (string) $property->myLatestPhoneRequest->status;
            }

            $canRequestOwnerPhone = (bool) ($user && !$isAdmin && !$isCreator && !$canViewOwnerPhone && $ownerPhoneRequestStatus !== 'PENDING');
        }

        return [
            'id'            => $this->id,
            'property'      => new PropertyResource($this->whenLoaded('property')),
            'status'        => $this->status,
            'visible_until' => $this->visible_until,
            'renew_count'   => $this->renew_count,
            'views_count'   => $this->views_count ?? 0,
            'direction'     => $this->property?->direction,
            'address'       => $this->property?->address,
            'street_name'   => $this->property?->street_name,
            'source_phone'  => $this->property?->source_phone,
            'source_code'   => $this->property?->source_code,
            'can_view_owner_phone' => $canViewOwnerPhone,
            'owner_phone_request_status' => $ownerPhoneRequestStatus,
            'can_request_owner_phone' => $canRequestOwnerPhone,

            // 'legal_status' => $this->property?->legal_status,
            'legal_status_label' => $this->property ? (\App\Support\PropertyOptionResolver::legalStatusMap()[$this->property->legal_status] ?? $this->property->legal_status) : null,
            // 'legal_docs' => $this->property?->legal_docs,
            'legal_doc' => $this->when(
                $this->relationLoaded('property') && $this->property?->relationLoaded('orderedFiles'),
                function () use ($user) {
                    // Lọc các file có purpose thuộc mục Pháp lý
                    $docs = $this->property->orderedFiles->filter(fn($file) => \App\Support\PropertyOptionResolver::isLegalDocumentPurpose($file->purpose));

                    $isCreator = $user && (int) $this->property->created_by === (int) $user->id;
                    $isAdmin   = $user && $user->isAdmin();

                    if (!$isCreator && !$isAdmin) {
                        return [];
                    }

                    return FileResource::collection($docs);
                },
                []
            ),
            'creator'       => new UserResource($this->whenLoaded('creator')),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,

            // Chỉ có khi query từ lịch sử xem (post_views JOIN)
            'viewed_at'     => $this->whenNotNull($this->viewed_at ?? null),

            // Ảnh của BĐS liên quan
            'images' => $this->when(
                $this->relationLoaded('property') && $this->property?->relationLoaded('images'),
                function () use ($user) {
                    $images = $this->property->images;

                    $isCreator = $user && $this->property->created_by === $user->id;
                    $isAdmin   = $user && $user->isAdmin();

                    if (!$isCreator && !$isAdmin) {
                        $images = $images->filter(fn($file) => $file->visibility === 'PUBLIC');
                    }

                    return FileResource::collection($images->values());
                },
                []
            ),
        ];
    }
}
