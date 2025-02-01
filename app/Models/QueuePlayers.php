<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QueuePlayers extends Model
{
    protected $table = 'queue_users';
    protected $fillable = ['queue_id', 'player_id'];

    public function player(): HasOne
    {
        return $this->hasOne(Player::class, 'id', 'player_id');
    }
}
