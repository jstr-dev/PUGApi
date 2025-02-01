<?php

namespace App\Services;

use App\Models\GameLobby;
use App\Models\GamePlayers;
use App\Models\Player;
use App\Models\Queue;
use Exception;

class GameService
{
    public function createFromQueue(Queue &$queue)
    {
        $slapshot = new Slapshot();
        $lastId = GameLobby::max('id');
        $lobbyName = 'EUSL ' . $queue->name . ' PUG (#' . ($lastId + 1) . ')';
        $password = \Str::random(8);

        $lobbyId = $slapshot->createLobby(
            $lobbyName,
            $password,
            true,
            'Slapville_Jumbo',
            7,
            4
        );

        $game = new GameLobby();
        $game->queue_id = $queue->getKey();
        $game->password = $password;
        $game->slapshot_id = $lobbyId;
        $game->name = $lobbyName;
        $game->save();

        foreach ($queue->players as $player) {
            $this->addPlayerToGame($game, $player->player, $player->team, $player->is_captain);
        }

        return $game;
    }

    public function addPlayerToGame(GameLobby $game, Player $player, string $team, ?bool $isCaptain = false)
    {
        $record = new GamePlayers();
        $record->game_lobby_id = $game->getKey();
        $record->player_id = $player->getKey();
        $record->team = $team;
        $record->is_captain = $isCaptain;
        $record->save();

        return $record;
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
