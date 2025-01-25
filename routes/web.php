<?php

use App\Http\Controllers\SteamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "Running.";
});

Route::controller(SteamController::class)->prefix('steam')->group(function() {
    Route::get('auth', 'authenticate');
});
