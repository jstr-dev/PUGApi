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
    public function addPlayerToQueue(Queue &$queue, Player $player)
    {
        $this->checkBan($player);

        $playerQueue = new QueuePlayers();
        $playerQueue->player_id = $player->getKey();
        $playerQueue->queue_id = $queue->getKey();
        $playerQueue->save();

        $playerQueue->player = $player;

        if (!($queue->players instanceof Illuminate\Database\Eloquent\Collection)) {
            $queue->load(['players', 'players.player']);
        } else {
            $queue->players->push($playerQueue);
        }
    }

    public function removePlayerFromQueue(Queue &$queue, Player $player)
    {
        $playerQueue = $queue->players->where('player_id', '=', $player->getKey())->first();

        if (!$playerQueue) {
            throw new BotAPIException('Not in queue', 'PLAYER_NOT_IN_QUEUE');
        }

        QueuePlayers::find($playerQueue->getKey())->delete();

        if (!($queue->players instanceof Illuminate\Database\Eloquent\Collection)) {
            $queue->load(['players', 'players.player']);
        } else {
            $queue->players->forget($queue->players->search(function ($player) use ($playerQueue) {
                return $player->id == $playerQueue->id;
            }));
        }
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
            $query->where('player_id', $player->getKey());
        })->first();

        if (!$queue) {
            throw new BotAPIException('Not in queue', 'PLAYER_NOT_IN_QUEUE');
        }

        $this->removePlayerFromQueue($queue, $player);

        if ($queue->state !== 'waiting') {
            $queue->state = 'waiting';

            foreach ($queue->players as $player) {
                $player->team = null;
                $player->is_captain = false;
                $player->save();
            }

            $queue->save();
            $queue->load(['players', 'players.player']);
        }

        return $queue;
    }

    // TODO: get captains by elo
    public function getCaptains(Queue &$queue)
    {
        $captain1 = $queue->players()->skip(6)->first();
        $captain2 = $queue->players()->skip(7)->first();

        return [$captain1, $captain2];
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

        if ($queue->players->whereNull('team')->count() === 1) {
            $player = $queue->players->whereNull('team')->first();
            $player->team = $queue->team_picking;
            $player->save();
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

        [$captain1, $captain2] = $this->getCaptains($queue);

        $captain1->is_captain = true;
        $captain2->is_captain = true;
        $captain1->team = 'home';
        $captain2->team = 'away';
        $captain1->save();
        $captain2->save();

        $queue->team_picking = 'home';
        $queue->state = 'picking';
        $queue->save();

        $queue->load(['players', 'players.player']);

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
        $queue->load(['players', 'players.player']);
    }

    // TODO: make this not bad.
    public function calculateNextPick(Queue &$queue)
    {
        $order = ['home', 'away', 'away', 'home', 'home', 'away'];
        // $order = ['home', 'home', 'home', 'home', 'home', 'home'];
        $remaining = $queue->players->whereNull('team')->count();
        \Log::info('Remaining: ' . $remaining);

        return array_reverse($order)[max($remaining - 1, 0)];
    }

    public function updateQueuePlayerAttr(Queue &$queue, QueuePlayers &$queuePlayer, string $attr, $value)
    {
        $queuePlayer->{$attr} = $value;
        $queuePlayer->save();

        if (!($queue->players instanceof Illuminate\Database\Eloquent\Collection)) {
            $queue->load(['players', 'players.player']);
        } else {
            $queue->players->each(function ($player) use ($queuePlayer, $attr, $value) {
                if ($player->getKey() === $queuePlayer->getKey()) {
                    $player->{$attr} = $value;
                }
            });
        }
    }

    public function pickPlayer(Queue &$queue, Player &$player, int $queuePlayerId)
    {
        if ($queue->state !== 'picking') {
            throw new BotAPIException('Queue is not picking', 'QUEUE_NOT_PICKING');
        }

        $callingPlayer = $queue->players->where('player_id', '=', $player->getKey())->first();

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

        $this->updateQueuePlayerAttr($queue, $pickedPlayer, 'team', $callingPlayer->team);

        $queue->team_picking = $this->calculateNextPick($queue);
        $queue->save();
    }
}
