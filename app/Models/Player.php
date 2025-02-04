<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $table = 'players';
    protected $fillable = ['discord_id'];

    public function queue()
    {
        $this->belongsTo(Queue::class)
            ->withPivot('queue_users');
    }
}
