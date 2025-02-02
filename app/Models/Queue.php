<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends Model
{
    protected $table = 'queue';
    protected $keyType = 'string';
    protected $with = ['players', 'players.player'];

    public function players(): HasMany
    {
        return $this->hasMany(QueuePlayers::class, 'queue_id', 'id');
    }
}
