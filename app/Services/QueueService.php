<?php

namespace App\Services;

use App\Exceptions\BotAPIException;
use App\Models\Player;
use App\Models\PlayerBan;
use App\Models\Queue;
use App\Models\QueuePlayers;
use Carbon\Carbon;
use Exception;

class QueueService
{
    public function addPlayer(Queue &$queue, Player $player)
    {
        $this->checkBan($player);
        $queue->players()->attach($player->id, ['updated_at' => now(), 'created_at' => now()]);
        $queue->load('players');
    }

    public function removePlayer(Queue &$queue, Player $player)
    {
        if ($queue->players->where('id', '=', $player->id)->count() === 0) {
            throw new BotAPIException('Not in queue', 'PLAYER_NOT_IN_QUEUE');
        }

        $queue->players()->detach($player->id);
        $queue->load('players');
    }

    public function checkBan(Player $player)
    {
        $playerBan = PlayerBan::where('player_id', '=', $player->getKey())
            ->where('expires_at', '>=', now())
            ->where('active', '=', 1)
            ->exists();

        if ($playerBan) {
            throw new BotAPIException('Player banned', 'PLAYER_BANNED');
        }
    }

    public function banPlayer(Player $admin, Player $player, Carbon $expiresAt, string $reason = '')
    {
        $currentBan = PlayerBan::where('player_id', '=', $player->getKey())
            ->where('expires_at', '>=', now())
            ->where('active', '=', 1)
            ->exists();

        if ($currentBan) {
            throw new BotAPIException('Player already banned', 'PLAYER_ALREADY_BANNED');
        }

        $playerBan = new PlayerBan();
        $playerBan->player_id = $player->getKey();
        $playerBan->admin_id = $admin->getKey();
        $playerBan->expires_at = $expiresAt;
        $playerBan->banned_at = now();
        $playerBan->reason = $reason;
        $playerBan->save();

        $queue = false;

        try {
            $queue = $this->kickPlayer($player);
        } finally {
            return [$playerBan, $queue];
        }
    }

    public function unbanPlayer(Player $player)
    {
        $playerBan = PlayerBan::where('player_id', '=', $player->getKey())
            ->where('expires_at', '>=', now())
            ->where('active', '=', 1)
            ->first();

        if (!$playerBan) {
            throw new BotAPIException('Player not banned', 'PLAYER_NOT_BANNED');
        }

        $playerBan->active = 0;
        $playerBan->save();
    }

    public function kickPlayer(Player $player)
    {
        $queue = Queue::whereHas('players', function ($query) use ($player) {
            $query->where('players.id', $player->getKey());
        })->first();

        if (!$queue) {
            throw new BotAPIException('Not in queue', 'PLAYER_NOT_IN_QUEUE');
        }

        $this->removePlayer($queue, $player);

        if ($queue->state !== 'waiting') {
            $queue->state = 'waiting';

            $queue->players()->syncWithPivotValues($queue->players->pluck('id')->toArray(), ['team' => null, 'is_captain' => false]);

            $queue->save();
            $queue->load('players');
        }

        return $queue;
    }

    public function getCaptains(Queue &$queue)
    {
        $homeCap = $queue->players->sortBy([['elo', 'desc']])->first();
        $awayCap = $queue->players->sortBy([['elo', 'desc']])->skip(1)->first();

        return [$homeCap, $awayCap];
    }

    public function progressState(Queue &$queue)
    {
        return match ($queue->state) {
            'waiting' => $this->tryStartPicking($queue),
            'picking' => $this->tryToFinish($queue),
            default => false,
        };
    }

    public function tryToFinish(Queue &$queue)
    {
        if ($queue->players->whereNull('team')->count() > 1) {
            return;
        }

        $player = $queue->players->whereNull('team')->first();
        if ($player) {
            $queue->players()->updateExistingPivot($player->id, ['team' => $queue->team_picking, 'updated_at' => now()]);
            $queue->load('players');
        }

        $game = (new GameService())->createFromQueue($queue);

        $queue->team_picking = null;
        $queue->state = 'finished';
        $queue->save();

        $queue->game = $game;
    }

    public function tryStartPicking(Queue &$queue)
    {
        if ($queue->players->count() !== 8) {
            return false;
        }

        [$homeCap, $awayCap] = $this->getCaptains($queue);

        $queue->players()->updateExistingPivot($homeCap->id, ['is_captain' => true, 'team' => 'home', 'updated_at' => now()]);
        $queue->players()->updateExistingPivot($awayCap->id, ['is_captain' => true, 'team' => 'away', 'updated_at' => now()]);

        $queue->team_picking = 'home';
        $queue->state = 'picking';
        $queue->save();

        $queue->load(['players']);

        return true;
    }

    public function reset(Queue &$queue, bool $dontKick = false)
    {
        $queue->team_picking = null;
        $queue->state = 'waiting';
        $queue->save();

        if (!$dontKick) {
            QueuePlayers::where('queue_id', '=', $queue->getKey())
                ->delete();
        }

        $queue->refresh();
        $queue->load(['players']);
    }

    public function calculateNextPick(Queue &$queue)
    {
        return 'home';
        $order = ['home', 'away', 'away', 'home', 'home', 'away'];
        $remaining = $queue->players->whereNull('team')->count();

        return array_reverse($order)[max($remaining - 1, 0)];
    }

    public function pickPlayer(Queue &$queue, Player &$player, int $queuePlayerId)
    {
        if ($queue->state !== 'picking') {
            throw new BotAPIException('Queue is not picking', 'QUEUE_NOT_PICKING');
        }

        $callingPlayer = $queue->players->where('id', '=', $player->id)->first();

        if (!$callingPlayer) {
            throw new BotAPIException('Player not in queue', 'PLAYER_NOT_IN_QUEUE');
        }

        if (!$callingPlayer->is_captain) {
            throw new BotAPIException('Player is not a captain', 'PLAYER_NOT_CAPTAIN');
        }

        if ($callingPlayer->team !== $queue->team_picking) {
            throw new BotAPIException('Player is not picking', 'TEAM_NOT_PICKING');
        }

        $pickedPlayer = $queue->players->where('id', '=', $queuePlayerId)->first();

        if (!$pickedPlayer) {
            throw new BotAPIException('Picked player is not in queue', 'PICKED_PLAYER_NOT_IN_QUEUE');
        }

        if ($pickedPlayer->team || $pickedPlayer->is_captain) {
            throw new BotAPIException('Picked player is already picked', 'PICKED_PLAYER_ALREADY_PICKED');
        }

        $queue->players()->updateExistingPivot($pickedPlayer->id, ['team' => $callingPlayer->team, 'updated_at' => now()]);
        $queue->team_picking = $this->calculateNextPick($queue);
        $queue->save();
        $queue->load('players');
    }
}
