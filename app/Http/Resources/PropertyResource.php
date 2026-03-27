<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User|null */
        $user = $request->user();

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
            'direction' => $this->direction,

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
                $isCreator = $user && (int) $this->created_by === (int) $user->id;
                $isAdmin   = $user && $user->isAdmin();

                if (!$isCreator && !$isAdmin) {
                    $images = $images->filter(fn($file) => $file->visibility === 'PUBLIC');
                }

                return FileResource::collection($images);
            }),

            // Người đăng
            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'avatar_url' => $this->creator?->avatar_url
                    ? (str_starts_with($this->creator->avatar_url, 'http')
                        ? $this->creator->avatar_url
                        : Storage::disk('public')->url($this->creator->avatar_url))
                    : null,
            ],

            // SĐT (đã mask tự động qua Model Accessor)
            'owner_phone' => $this->owner_phone,
            'source_phone' => $this->source_phone,
            'source_code' => $this->source_code,

            // Pháp lý
            // 'legal_status' => $this->legal_status,
            'legal_status_label' => \App\Support\PropertyOptionResolver::legalStatusMap()[$this->legal_status] ?? $this->legal_status,
            'legal_doc' => $this->whenLoaded('orderedFiles', function () use ($user) {
                // Lọc các file có purpose thuộc mục Pháp lý
                $docs = $this->orderedFiles->filter(fn($file) => \App\Support\PropertyOptionResolver::isLegalDocumentPurpose($file->purpose));

                $isCreator = $user && (int) $this->created_by === (int) $user->id;
                $isAdmin   = $user && $user->isAdmin();

                if (!$isCreator && !$isAdmin) {
                    return [];
                }

                return FileResource::collection($docs);
            }),

            // Thời gian
            'created_at' => $this->created_at,

            // Địa chỉ
            'address' => $this->address,
            'street_name' => $this->street_name,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'google_map_url' => $this->google_map_url,
            'category_name' => $this->category?->name,
            'area_name' => $this->areaLocation?->name,

            // Trạng thái
            'approval_status' => $this->approval_status,
            'posts_count' => $this->whenCounted('posts'),
            'has_post' => $this->when(
                isset($this->posts_count),
                fn() => (int) $this->posts_count > 0
            ),
        ];
    }
}
