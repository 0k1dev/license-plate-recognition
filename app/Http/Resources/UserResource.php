<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url ? (str_starts_with($this->avatar_url, 'http') ? $this->avatar_url : \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_url)) : null,
            // Chỉ trả về roles nếu cần, bỏ qua đống permissions đồ sộ
            'roles' => $this->when($this->relationLoaded('roles'), fn() => $this->roles->pluck('name')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
