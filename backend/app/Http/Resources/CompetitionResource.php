<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'name' => $this->name,
            'code' => $this->code,
            'area_name' => $this->area_name,
            'emblem' => $this->emblem,
            'season' => $this->season,
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'teams' => $this->whenLoaded('teams', fn () => TeamResource::collection($this->teams)),
        ];
    }
}
