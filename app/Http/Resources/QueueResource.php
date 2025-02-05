<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QueueResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'discord_channel_id' => $this->discord_channel_id,
            'state' => $this->state,
            'team_picking' => $this->team_picking,
            'players' => $this->players->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->name,
                    'elo' => $player->elo ?? config('services.pug.default_elo'),
                    'discord_id' => $player->discord_id,
                    'team' => $player->team,
                    'is_captain' => $player->is_captain,
                    'updated_at' => $player->updated_at,
                ];
            }),
            'game' => $this->game ?? null
        ];
    }
}
