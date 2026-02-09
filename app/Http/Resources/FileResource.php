<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = $this->visibility === 'PUBLIC' ? 'public' : 'local';

        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'filename' => $this->filename,
            'url' => $this->visibility === 'PUBLIC'
                ? Storage::disk('public')->url($this->path)
                : route('files.download', ['file' => $this->id]),
            'thumbnail_url' => $this->thumbnail_path && $this->visibility === 'PUBLIC'
                ? Storage::disk('public')->url($this->thumbnail_path)
                : null,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'is_image' => $this->is_image,
            'purpose' => $this->purpose,
            'visibility' => $this->visibility,
            'order' => $this->order,
            'is_primary' => $this->is_primary,
            'uploaded_at' => $this->created_at,
        ];
    }
}
