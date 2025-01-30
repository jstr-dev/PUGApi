<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Queue;
use App\Models\QueuePlayers;
use Illuminate\Http\Request;

// TODO: error codes!
class InternalAPIController extends Controller
{
    public function getQueues()
    {
        $queues = Queue::get();
        $transformed = [];

        foreach ($queues as $queue) {
            $transformed[] = $queue->transform();
        }

        return response()->json($transformed);
    }

    public function getQueue(string $queueId)
    {
        $queue = Queue::where('id', '=', $queueId)
            ->with('players.player')
            ->first();

        if (!$queue) {
            return response()->json(['error' => 'Queue not found.', 'code' => 'QUEUE_NOT_FOUND'], 404);
        }

        return response()->json($queue->transform());
    }

    public function postJoinQueue(Request $request, string $queueId)
    {
        $player = Player::where('discord_id', $request->discord_id)->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found.', 'code' => 'PLAYER_NOT_FOUND'], 404);
        }

        if (!Queue::where('id', $queueId)->exists()) {
            return response()->json(['error' => 'Queue not found.', 'code' => 'QUEUE_NOT_FOUND'], 404);
        }

        $playerQueue = QueuePlayers::where('player_id', $player->id)->first();

        if ($playerQueue) {
            if ($playerQueue->queue_id === $queueId) {
                return response()->json(['error' => 'Player already in queue.', 'code' => 'PLAYER_ALREADY_IN_QUEUE'], 400);
            }

            return response()->json(['error' => 'Player already in another queue.', 'code' => 'PLAYER_ALREADY_IN_ANOTHER_QUEUE'], 400);
        }

        if (QueuePlayers::where('queue_id', $queueId)->count() >= 8) {
            return response()->json(['error' => 'Queue is full.', 'code' => 'QUEUE_FULL'], 400);
        }

        $playerQueue = new QueuePlayers();
        $playerQueue->player_id = $player->id;
        $playerQueue->queue_id = $queueId;
        $playerQueue->save();

        return response()->json(Queue::find($queueId)->first()->transform());
    }

    public function postLeaveQueue(Request $request, string $queueId)
    {
        $player = Player::where('discord_id', $request->discord_id)->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found.', 'code' => 'PLAYER_NOT_FOUND'], 404);
        }

        if (!Queue::where('id', $queueId)->exists()) {
            return response()->json(['error' => 'Queue not found.', 'code' => 'QUEUE_NOT_FOUND'], 404);
        }

        $playerQueue = QueuePlayers::where('player_id', $player->id)->where('queue_id', $queueId)->first();

        if (!$playerQueue) {
            return response()->json(['error' => 'Player is not in the queue', 'code' => 'PLAYER_NOT_IN_QUEUE'], 400);
        }

        $playerQueue->delete();

        return response()->json(Queue::find($queueId)->first()->transform());
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

    public function getUserAuthenticated(Request $request)
    {
        if (!$request->has('discord_id')) {
            return response()->json(['error' => 'Please give a valid "discord_id" parameter.'], 400);
        }

        $exists = Player::where('discord_id', $request->discord_id)->exists();

        return response()->json(['authenticated' => $exists]);
    }
}
