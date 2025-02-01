<?php

namespace App\Services;

use App\Models\GameLobby;
use App\Models\GamePlayers;
use App\Models\Player;
use Exception;

class GameService
{
    public function addPlayerToGame(GameLobby $game, Player $player, int $team, ?bool $isCaptain = false)
    {
        $record = new GamePlayers();
        $record->game_lobby_id = $game->getKey();
        $record->player_id = $player->getKey();
        $record->team = $team;
        $record->is_captain = $isCaptain;
        $record->save();

        return $record;
    }

    public function createWithCaptains(Player $captain1, Player $captain2): GameLobby
    {
        $game = new GameLobby();
        $game->slapshot_id = ''; // TODO - make nullable
        $game->password = '';
        $game->save();

        $this->addPlayerToGame($game, $captain1, 0, true);
        $this->addPlayerToGame($game, $captain2, 1, true);

        return $game;
    }

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
