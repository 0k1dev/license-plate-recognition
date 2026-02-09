<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User */
        $user = $request->user();
        $canViewPhone = $user && $user->can('viewOwnerPhone', $this->resource);

        // Masking Phone
        $phone = $this->owner_phone;
        if (!$canViewPhone) {
            $phone = $phone ? (substr($phone, 0, 3) . '*****' . substr($phone, -3)) : null;
        }


        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,

            // Giá và diện tích
            'price' => $this->price,
            'area' => $this->area,

            // Chi tiết căn hộ/nhà (quan trọng)
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,

            // Hình ảnh chính
            'primary_image_url' => $this->primary_image_url,
            'primary_thumbnail_url' => $this->primary_thumbnail_url,

            // Số lượng ảnh
            'images_count' => $this->whenLoaded('images', function () {
                return $this->images->count();
            }, 0),

            // Danh sách ảnh chi tiết
            'images' => $this->whenLoaded('images', function () use ($user) {
                $images = $this->images;

                // Chỉ trả về PUBLIC images, trừ khi là creator hoặc admin
                $isCreator = $user && $this->created_by === $user->id;
                $isAdmin = $user && ($user->isSuperAdmin() || $user->isOfficeAdmin());

                if (!$isCreator && !$isAdmin) {
                    $images = $images->filter(fn($file) => $file->visibility === 'PUBLIC');
                }

                return FileResource::collection($images);
            }),

            // Người đăng
            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'avatar_url' => $this->creator?->avatar_url,
            ],

            // SĐT đã mask
            'owner_phone' => $phone,

            // Thời gian
            'created_at' => $this->created_at,

            // Địa chỉ
            'address' => $this->address,
            'category_name' => $this->category?->name,
            'area_name' => $this->areaLocation?->name,

            // Trạng thái
            'approval_status' => $this->approval_status,
        ];
    }
}
