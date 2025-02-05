<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends Model
{
    protected $table = 'queue';
    protected $keyType = 'string';
    protected $with = ['players'];

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'queue_users')
            ->select(['players.id', 'players.name', 'players.discord_id', 'queue_users.team', 'queue_users.is_captain', 'queue_users.updated_at', 'player_statistics.elo'])
            ->leftJoin('player_statistics', function ($join) {
                $join->on('players.id', '=', 'player_statistics.player_id')
                    ->on('player_statistics.queue_id', '=', 'queue_users.queue_id');
            });
    }
}
