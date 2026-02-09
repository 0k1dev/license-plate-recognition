<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reporter_id' => $this->reporter_id,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'type' => $this->type,
            'content' => $this->content,
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
