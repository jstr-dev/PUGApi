<?php

namespace App\DTO;

use App\Models\GameLobby;
use Carbon\Carbon;

class SlapshotPeriodDTO
{
    protected array $player;
    protected Carbon $createdAt;
    protected int $gameLobbyId;
    protected string $matchSlapshotId;
    protected int $period;

    public function __construct(array $player, Carbon $createdAt, GameLobby $gameLobby, string $matchSlapshotId)
    {
        $this->player = $player;
        $this->createdAt = $createdAt;
        $this->gameLobbyId = $gameLobby->getKey();
        $this->period = $gameLobby->period_count;
        $this->matchSlapshotId = $matchSlapshotId;
    }

    public static function statColumns(): array
    {
        return [
            'score',
            'wins',
            'losses',
            'goals',
            'shots',
            'shutouts',
            'shutouts_against',
            'was_mercy_ruled',
            'conceded_goals',
            'post_hits',
            'games_played',
            'blocks',
            'faceoffs_won',
            'faceoffs_lost',
            'has_mercy_ruled',
            'contributed_goals',
            'possession_time_sec',
            'saves',
            'primary_assists',
            'secondary_assists',
            'assists',
            'turnovers',
            'takeaways',
            'periods_played'
        ];
    }

    public function toArray(): array
    {
        return array_merge(
            [
                'game_lobby_id' => $this->gameLobbyId,
                'match_slapshot_id' => $this->matchSlapshotId,
                'player_slapshot_id' => $this->player['game_user_id'],
                'team' => $this->player['team'],
                'created_at' => $this->createdAt->toDateTimeString(),
                'username' => $this->player['username'],
                'period_number' => $this->period
            ],

            $this->filledStats()
        );
    }
    
    private function getDefaultForStat(string $stat): int
    {
        if ($stat === 'periods_played') {
            return 1;
        }

        return 0;
    }

    protected function filledStats(): array
    {
        $inputStats = $this->player['stats'] ?? [];
        $result = [];

        foreach (self::statColumns() as $key) {
            $result[$key] = $inputStats[$key] ?? $this->getDefaultForStat($key);
        }

        return $result;
    }
}

