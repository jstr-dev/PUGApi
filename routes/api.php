<?php

use App\Http\Controllers\InternalAPIController;
use Illuminate\Support\Facades\Route;

Route::controller(InternalAPIController::class)
    ->middleware('auth:sanctum')
    ->prefix('internal')
    ->group(function () {
        Route::get('queues', 'getQueues');
        Route::get('queue/{queueId}', 'getQueue');
        Route::post('queue/{queueId}/join', 'postJoinQueue');
        ROute::post('queue/{queueId}/leave', 'leaveQueue');
        Route::post('queue/create', 'createQueue');

        Route::get('user/authenticated', 'getUserAuthenticated');
    });
