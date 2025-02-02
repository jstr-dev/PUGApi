<?php

use App\Http\Controllers\InternalAPIController;
use App\Http\Controllers\SlapshotController;
use Illuminate\Support\Facades\Route;

Route::controller(InternalAPIController::class)
    ->middleware('auth:sanctum')
    ->prefix('internal')
    ->group(function () {
        Route::get('queues', 'getQueues');
        Route::get('queue/{queueId}', 'getQueue');
        Route::post('queue/{queueId}/join', 'postJoinQueue');
        Route::post('queue/{queueId}/leave', 'postLeaveQueue');
        Route::post('queue/{queueId}/pick', 'postPickQueue');
        Route::post('queue/{queueId}/reset', 'postResetQueue');
        Route::post('queue/create', 'createQueue');
        Route::post('queue/kick', 'postKickQueue');
        Route::post('queue/ban', 'postBanPlayer');
        Route::post('queue/unban', 'postUnbanPlayer');

        Route::get('game/{gameId}/password', 'getGamePassword');

        Route::get('user/authenticated', 'getUserAuthenticated');
    });

Route::controller(SlapshotController::class)->prefix('slapshot')->group(function () {
    Route::post('lobby_webhook', 'postLobbyWebhook');
});
