<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Queue;
use App\Models\QueuePlayers;

class QueueService
{
    public function addPlayerToQueue(Queue $queue, Player $player)
    {
        $playerQueue = new QueuePlayers();
        $playerQueue->player_id = $player->getKey();
        $playerQueue->queue_id = $queue->getKey();
        $playerQueue->save();

        return true;
    }

    public function removePlayerFromQueue(Queue $queue, Player $player)
    {
        $playerQueue = QueuePlayers::where('player_id', $player->getKey())->where('queue_id', $queue->getKey())->first();
        $playerQueue->delete();

        return true;
    }

    public function getCaptains(Queue &$queue)
    {
        // this is where we'd check for elo
        $captain1 = $queue->players()->skip(6)->first();
        $captain2 = $queue->players()->skip(7)->first();

        return [$captain1, $captain2];
    }

    public function progressState(Queue $queue)
    {
        $gameService = new GameService();

        $queue->refresh();

        if ($queue->state != 'waiting' || $queue->players()->count() !== 8) {
            return false;
        }

        [$captain1, $captain2] = $this->getCaptains($queue);

        $captain1->fill(['is_captain' => true, 'team' => 0])->save();
        $captain2->fill(['is_captain' => true, 'team' => 1])->save();

        $game = $gameService->createWithCaptains($captain1->player, $captain2->player);

        // Currently picking
        $queue->currently_picking_id = $captain1->player->getKey();
        $queue->current_game_id = $game->getKey();

        $queue->state = 'picking';
        $queue->save();

        return true;
    }
}
