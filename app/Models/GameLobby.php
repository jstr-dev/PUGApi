<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameLobby extends Model
{
    protected $table = 'game_lobbies';

    public function players()
    {
        return $this->hasMany(GamePlayers::class);
    }
}
