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
        return $this->belongsToMany(Player::class, 'queue_users');
    }
}
