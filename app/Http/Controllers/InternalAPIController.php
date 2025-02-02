<?php

namespace App\Http\Controllers;

use App\Exceptions\BotAPIException;
use App\Models\GameLobby;
use App\Models\Player;
use App\Models\Queue;
use App\Models\QueuePlayers;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Http\Request;

// TODO: error codes!
class InternalAPIController extends Controller
{
    public function getQueues()
    {
        return response()->json(Queue::get());
    }

    public function getQueue(string $queueId)
    {
        $queue = Queue::where('id', '=', $queueId)
            ->with('players.player')
            ->first();

        if (!$queue) {
            return response()->json(['error' => 'Queue not found.', 'code' => 'QUEUE_NOT_FOUND'], 404);
        }

        return response()->json($queue);
    }

    public function postJoinQueue(QueueService $queueService, Request $request, string $queueId)
    {
        $player = Player::where('discord_id', $request->discord_id)->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found.', 'code' => 'PLAYER_NOT_FOUND'], 404);
        }

        $queue = Queue::where('id', $queueId)->first();
        if (!$queue) {
            return response()->json(['error' => 'Queue not found.', 'code' => 'QUEUE_NOT_FOUND'], 404);
        }

        $playerQueue = QueuePlayers::where('player_id', $player->id)->first();

        if ($playerQueue) {
            if ($playerQueue->queue_id === $queueId) {
                return response()->json(['error' => 'Player already in queue.', 'code' => 'PLAYER_ALREADY_IN_QUEUE'], 400);
            }

            return response()->json(['error' => 'Player already in another queue.', 'code' => 'PLAYER_ALREADY_IN_ANOTHER_QUEUE'], 400);
        }

        $queueCount = $queue->players->count();
        if ($queueCount >= 8) {
            return response()->json(['error' => 'Queue is full.', 'code' => 'QUEUE_FULL'], 400);
        }

        try {
            $queueService->addPlayerToQueue($queue, $player);
        } catch (BotAPIException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getApiErrorCode()], 400);
        }

        $queueService->progressState($queue);

        return response()->json($queue);
    }

    public function postLeaveQueue(Request $request, string $queueId, QueueService $queueService)
    {
        $player = Player::where('discord_id', $request->discord_id)->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found.', 'code' => 'PLAYER_NOT_FOUND'], 404);
        }

        $queue = Queue::where('id', $queueId)->first();
        if (!$queue) {
            return response()->json(['error' => 'Queue not found.', 'code' => 'QUEUE_NOT_FOUND'], 404);
        }

        if ($queue->state != 'waiting') {
            return response()->json(['error' => 'Queue is not waiting.', 'code' => 'QUEUE_IN_PROGRESS'], 400);
        }

        try {
            $queueService->removePlayerFromQueue($queue, $player);
        } catch (BotAPIException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getApiErrorCode()], 400);
        }

        return response()->json($queue);
    }

    public function postPickQueue(Request $request, string $queueId, QueueService $queueService)
    {
        $request->validate([
            'discord_id' => 'required',
            'queue_player_id' => 'required',
        ]);

        $callingPlayer = Player::where('discord_id', $request->discord_id)->first();

        if (!$callingPlayer) {
            return response()->json(['error' => 'Player not found.', 'code' => 'PLAYER_NOT_FOUND'], 404);
        }

        $queue = Queue::where('id', $queueId)->first();

        if (!$queue) {
            return response()->json(['error' => 'Queue not found.', 'code' => 'QUEUE_NOT_FOUND'], 404);
        }

        try {
            $queueService->pickPlayer($queue, $callingPlayer, $request->queue_player_id);
            $queueService->progressState($queue);

            return response()->json($queue);
        } catch (BotAPIException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getApiErrorCode()], 400);
        }
    }

    public function postBanPlayer(Request $request, QueueService $queueService)
    {
        $request->validate([
            'discord_id' => 'required',
            'admin_discord_id' => 'required',
            'reason' => 'required',
            'expires_at' => ['required', 'numeric'],
        ]);

        \Log::info(print_r($request->all(), true));

        $player = Player::where('discord_id', $request->discord_id)->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found.', 'code' => 'PLAYER_NOT_FOUND'], 404);
        }

        $admin = Player::where('discord_id', $request->admin_discord_id)->first();

        if (!$admin) {
            return response()->json(['error' => 'Admin not found.', 'code' => 'ADMIN_NOT_FOUND'], 404);
        }

        try {
            [$ban, $queue] = $queueService->banPlayer($admin, $player, Carbon::createFromTimestamp($request->expires_at), $request->reason);

            return response()->json([
                'ban' => $ban,
                'did_kick' => !empty($queue),
                'queue' => $queue,
            ]);
        } catch (BotAPIException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getApiErrorCode()], 400);
        }
    }

    public function postUnbanPlayer(Request $request, QueueService $queueService)
    {
        $request->validate([
            'discord_id' => 'required',
        ]);

        $player = Player::where('discord_id', $request->discord_id)->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found.', 'code' => 'PLAYER_NOT_FOUND'], 404);
        }

        try {
            $queueService->unbanPlayer($player);
            return response()->json(['message' => 'Player unbanned.']);
        } catch (BotAPIException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getApiErrorCode()], 400);
        }
    }

    public function createQueue(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'name' => 'required',
            'discord_channel_id' => 'required',
        ]);

        if (Queue::where('id', '=', $request->id)->exists()) {
            return response()->json(['error' => 'Queue already exists.'], 400);
        }

        $queue = new Queue();
        $queue->fill($request->all());
        $queue->save();

        return response()->json(['message' => 'Queue created.']);
    }

    public function postResetQueue(QueueService $queueService, string $queueId)
    {
        $queue = Queue::where('id', $queueId)->first();

        if (!$queue) {
            return response()->json(['error' => 'Queue not found.', 'code' => 'QUEUE_NOT_FOUND'], 404);
        }

        $queueService->reset($queue);

        return response()->json($queue);
    }

    public function postKickQueue(Request $request, QueueService $queueService)
    {
        $request->validate([
            'discord_id' => 'required',
        ]);

        $player = Player::where('discord_id', $request->discord_id)->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found.', 'code' => 'PLAYER_NOT_FOUND'], 404);
        }

        try {
            $queue = $queueService->kickPlayer($player);
            return response()->json($queue);
        } catch (BotAPIException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getApiErrorCode()], 400);
        }
    }

    public function getUserAuthenticated(Request $request)
    {
        if (!$request->has('discord_id')) {
            return response()->json(['error' => 'Please give a valid "discord_id" parameter.'], 400);
        }

        $exists = Player::where('discord_id', $request->discord_id)->exists();

        return response()->json(['authenticated' => $exists]);
    }

    public function getGamePassword(Request $request, int $gameId)
    {
        $player = Player::where('discord_id', $request->discord_id)->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found.', 'code' => 'PLAYER_NOT_FOUND'], 404);
        }

        $game = GameLobby::where('id', $gameId)->first();

        if (!$game) {
            return response()->json(['error' => 'Game not found.', 'code' => 'GAME_NOT_FOUND'], 404);
        }

        if (!$game->players()->where('player_id', $player->id)->exists()) {
            return response()->json(['error' => 'Player not in game.', 'code' => 'PLAYER_NOT_IN_GAME'], 400);
        }

        return response()->json(['password' => $game->password]);
    }
}
