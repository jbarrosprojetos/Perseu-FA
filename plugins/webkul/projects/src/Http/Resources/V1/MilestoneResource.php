<?php

namespace Webkul\Project\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Security\Http\Resources\V1\UserResource;

class MilestoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'deadline'     => $this->deadline,
            'is_completed' => (bool) $this->is_completed,
            'completed_at' => $this->completed_at,
            'processo_id'  => $this->processo_id,
            'creator_id'   => $this->creator_id,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'processo'     => new ProjectResource($this->whenLoaded('processo')),
            'creator'      => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
