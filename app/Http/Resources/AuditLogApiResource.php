<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actor' => $this->whenLoaded('actor', fn() => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ]),
            'action' => $this->action,
            'target_type' => $this->target_type ? class_basename($this->target_type) : null,
            'target_id' => $this->target_id,
            'payload' => $this->payload,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
