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

    private function addPeriodToGame(GameLobby &$lobby, string $matchId)
    {
        // $data = $slapshot->getMatch($matchId);
    }

    private function tryEndGame(GameLobby &$lobby)
    {
        // Try to end the game... blah
        // Calculate Elo
    }

    public function processWebhook(GameLobby $lobby, string $matchId, string $event)
    {
        // Update game table state
        // Try that slapshot delete lobby call
        // Record to GamePeriods table
        // On finalise game update Elo ratings (MAIN)

        $matchData = (new Slapshot())->getMatchDetails($matchId);
        \Log::info(json_encode($matchData, JSON_PRETTY_PRINT));

        switch ($event) {
            case 'match_started':
                $lobby->state = 'playing';
                $lobby->save();
                break;
            case 'match_ended':
            case 'stats_reported':
                // if ($lobby->periods->where('slapshot_id', '=', $matchId)->count() > 0) {
                //     break;
                // }

                // $this->addPeriodToGame($lobby, $matchId);
                // $this->tryEndGame($lobby);
            case 'lobby_destroyed':
            default:
            // throw new Exception('Unknown lobby event');
        }
    }

    private function updatePlayerStatistics(GameLobby $game)
    {
        $game->load(['periods', 'players']);

        $winner = $game->winner; // 'home' OR 'away'

        foreach ($game->players as $player) {
            // Ensure they were a participant in the game.
            $lastPeriodPlayerPlayed = $game->periods->where('slapshot_id', '=', $player->slapshot_id)->last();
            $playerTeam = $lastPeriodPlayerPlayed->team;
            $hasWon = $playerTeam === $winner;
            // $eloDifference = $this->calculateEloDifference( $playerElo, $teamAverageElo, $hasWon );
            // PlayerStatitics::where(...)->elo += $eloDifference
        }
    }
}
