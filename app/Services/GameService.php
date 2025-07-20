<?php

namespace App\Services;

use App\DTO\SlapshotPeriodDTO;
use App\Models\GameLobby;
use App\Models\GamePeriod;
use App\Models\GamePlayers;
use App\Models\Player;
use App\Models\Queue;
use Carbon\Carbon;
use DB;
use Exception;

class GameService
{
    public function createFromQueue(Queue &$queue)
    {
        return DB::transaction(function () use ($queue) {
            $slapshot = new Slapshot();

            $game = new GameLobby();
            $game->queue_id = $queue->getKey();
            $game->password = '';
            $game->slapshot_id = 'temp-' . rand(100000, 999999);
            $game->name = $queue->name;
            $game->save();

            $lobbyName = 'EUSL ' . $queue->name . ' PUG (#' . ($game->getKey()) . ')';
            $password = \Str::random(8);

            $lobbyId = $slapshot->createLobby(
                $lobbyName,
                $password,
                true,
                'Slapville_Jumbo',
                7,
                4
            );

            if (!$lobbyId) {
                throw new Exception('Failed to create lobby.');
            }

            $game->password = $password;
            $game->slapshot_id = $lobbyId;
            $game->save();

            foreach ($queue->players as $player) {
                $this->addPlayer($game, $player, $player->team, $player->is_captain);
            }

            return $game;
        });
    }

    public function addPlayer(GameLobby $game, Player $player, string $team, ?bool $isCaptain = false)
    {
        $record = new GamePlayers();
        $record->game_lobby_id = $game->getKey();
        $record->player_id = $player->getKey();
        $record->team = $team;
        $record->is_captain = $isCaptain;
        $record->save();

        return $record;
    }

    private function tryEndGame(GameLobby &$lobby)
    {
        // Try to end the game... blah
        // Calculate Elo
    }

    public function processWebhook(GameLobby $lobby, string $matchId, string $event)
    {
        switch ($event) {
            case 'match_started':
                $lobby->state = 'playing';
                $lobby->save();
                break;
            case 'match_ended':
            case 'stats_reported':
                $this->processGamePeriod($lobby, $matchId); 
                break;
            case 'lobby_destroyed':
            default:
            // throw new Exception('Unknown lobby event');
        }
    }
    
    private function processGamePeriod(GameLobby &$lobby, string $matchId)
    {
        // Check if the period has already been processed.
        if ($lobby->periods()->where('match_slapshot_id', '=', $matchId)->exists()) {
            return;
        }

        $slapshot = app(Slapshot::class);
        $data = $slapshot->getMatchDetails($matchId);

        \Log::info('match data retrieved', $data);

        DB::transaction(function() use (&$lobby, &$data, $matchId) {
            if (empty($data['game_stats']) || empty($data['game_stats']['players'])) {
                throw new Exception('No game stats');
            }

            $lobby->period_count = $data['game_stats']['current_period'];
            $lobby->save();
            $periodCreatedAt = Carbon::parse($data['created']);
            $insert = [];

            foreach ($data['game_stats']['players'] as $player) {
                $insert[] = (new SlapshotPeriodDTO($player, $periodCreatedAt, $lobby, $matchId))
                    ->toArray();
            }

            GamePeriod::insertOrIgnore($insert);

            // $this->updatePlayerStatistics($lobby);
        });
    }

    public function calculateEloDifference(int $eloA, int $eloB, bool $won, int $kFactor = 40)
    {
        $expectedScore = 1 / (1 + pow(10, ($eloB - $eloA) / 400));
        $actualScore = $won ? 1 : 0;

        return round($kFactor * ($actualScore - $expectedScore));
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
