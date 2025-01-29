<?php

namespace App\Services;

use App\Models\GameLobby;
use Exception;

class GameService
{
    public function processWebhook(GameLobby $lobby, string $event)
    {
        \Log::info('Hey!');
        \Log::info($event);
        \Log::info(print_r($lobby, true));

        switch ($event) {
            case 'match_started':
            case 'match_ended':
            case 'stats_reported':
            case 'lobby_destroyed':
            default:
                throw new Exception('Unknown lobby event');
        }
    }
}
