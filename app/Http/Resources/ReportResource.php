<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasPost = $this->relationLoaded('post') && $this->post;
        $hasProperty = $hasPost && $this->post->relationLoaded('property') && $this->post->property;
        $hasReportedUser = $hasPost && $this->post->relationLoaded('creator') && $this->post->creator;

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reporter_id' => $this->reporter_id,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'type' => $this->type,
            'content' => $this->content,
            'post' => $this->when(
                $hasPost,
                fn() => [
                    'id' => $this->post->id,
                    'status' => $this->post->status,
                    'visible_until' => $this->post->visible_until?->toIso8601String(),
                ]
            ),
            'property' => $this->when(
                $hasProperty,
                fn() => [
                    'id' => $this->post->property->id,
                    'title' => $this->post->property->title,
                    'address' => $this->post->property->address,
                ]
            ),
            'reported_user' => $this->when(
                $hasReportedUser,
                fn() => [
                    'id' => $this->post->creator->id,
                    'name' => $this->post->creator->name,
                    'email' => $this->post->creator->email,
                ]
            ),
            'evidence_files' => FileResource::collection($this->whenLoaded('files')),
            'evidence_count' => $this->whenLoaded('files', fn() => $this->files->count(), 0),
            'status' => $this->status,
            'action' => $this->action,
            'admin_note' => $this->admin_note,
            'resolved_by' => $this->resolved_by,
            'resolver' => new UserResource($this->whenLoaded('resolver')),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
