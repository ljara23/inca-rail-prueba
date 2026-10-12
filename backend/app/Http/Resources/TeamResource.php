<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'tla' => $this->tla,
            'crest' => $this->crest,
            'players' => $this->whenLoaded('players', fn () => PlayerResource::collection($this->players)),
        ];
    }
}
