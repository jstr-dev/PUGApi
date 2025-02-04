<?php

namespace App\Http\Controllers;

use App\Models\GameLobby;
use App\Services\GameService;
use Illuminate\Http\Request;

class SlapshotController extends Controller
{
    public function postLobbyWebhook(GameService $gameService, Request $request)
    {
        \Log::info('------------------------- SLAPSHOT WEBHOOK CALL ---------------------');
        \Log::info(json_encode($request->all(), JSON_PRETTY_PRINT));
        \Log::info('----------------------------------------------------------------------');

        $request->validate([
            'lobby_id' => 'required',
            'match_id' => 'required',
            'event' => 'required',
        ]);

        $lobby = GameLobby::where('slapshot_id', $request->lobby_id)->first();

        if (!$lobby) {
            return response()->json(['error' => 'Lobby not found.'], 404);
        }

        $gameService->processWebhook($lobby, $request->match_id, $request->event);
    }
}
