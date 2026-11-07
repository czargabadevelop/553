<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GameController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Versioned JSON API shared by the web app and the future Next.js + Capacitor
| client. Authorization and validation match the web application; tokens are
| Sanctum personal access tokens.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/ping', fn () => response()->json(['status' => 'ok', 'version' => 'v1']))->name('ping');

    Route::middleware('throttle:10,1')->post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', MeController::class)->name('me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms/{room}/join', [RoomController::class, 'join'])->name('rooms.join');
        Route::post('/rooms/{room}/leave', [RoomController::class, 'leave'])->name('rooms.leave');
        Route::post('/rooms/{room}/ready', [RoomController::class, 'ready'])->name('rooms.ready');

        Route::get('/games/{game}', [GameController::class, 'show'])->name('games.show');
        Route::post('/games/{game}/hands', [GameController::class, 'submit'])->name('games.submit');

        Route::get('/wallet', [WalletController::class, 'show'])->name('wallet.show');
        Route::post('/wallet/deposits', [WalletController::class, 'deposit'])->name('wallet.deposits.store');
        Route::post('/wallet/withdrawals', [WalletController::class, 'withdrawal'])->name('wallet.withdrawals.store');
    });
});

// Unknown API routes always answer with JSON, never an Inertia page.
Route::fallback(fn () => response()->json(['message' => 'Not found.'], 404));
