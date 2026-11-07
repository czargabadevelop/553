<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\FinanceController as AdminFinance;
use App\Http\Controllers\Admin\GameController as AdminGame;
use App\Http\Controllers\Admin\PlayerController as AdminPlayer;
use App\Http\Controllers\Admin\RoomController as AdminRoom;
use App\Http\Controllers\Admin\SettingsController as AdminSettings;
use App\Http\Controllers\Auth\Login;
use App\Http\Controllers\CRUD;
use App\Http\Controllers\Player\DashboardController;
use App\Http\Controllers\Player\GameController;
use App\Http\Controllers\Player\HistoryController;
use App\Http\Controllers\Player\LobbyController;
use App\Http\Controllers\Player\ProfileController;
use App\Http\Controllers\Player\WalletController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Everything here is for guests (not logged in).
Route::middleware('guest')->group(function () {
    // Login / root page.
    Route::get('/', [Login::class, 'showLoginForm'])->name('login');

    // Found in app/Http/Controllers/Auth/Login.php
    Route::post('/login', [Login::class, 'login'])->name('login.submit');
});

// Everything here requires a logged in user.
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Lobby and seating.
    Route::get('/lobby', [LobbyController::class, 'index'])->name('lobby');
    Route::post('/lobby/{room}/join', [LobbyController::class, 'join'])->name('lobby.join');
    Route::post('/lobby/{room}/leave', [LobbyController::class, 'leave'])->name('lobby.leave');
    Route::post('/lobby/{room}/ready', [LobbyController::class, 'ready'])->name('lobby.ready');

    // Poker table, hand submission and results.
    Route::get('/games/{game}', [GameController::class, 'show'])->name('games.show');
    Route::post('/games/{game}/hands', [GameController::class, 'submit'])->name('games.submit');
    // A seated player may ask the server to resolve an expired round; the
    // server re-checks the deadline, so the client cannot force anything early.
    Route::post('/games/{game}/timeout', [GameController::class, 'resolveTimeout'])
        ->middleware('throttle:12,1')->name('games.timeout');

    // History and wallet.
    Route::get('/history', [HistoryController::class, 'index'])->name('history');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::post('/wallet/deposits', [WalletController::class, 'storeDeposit'])->name('wallet.deposits.store');
    Route::post('/wallet/withdrawals', [WalletController::class, 'storeWithdrawal'])->name('wallet.withdrawals.store');

    // Profile and responsible gaming.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Administration. Every route is enforced on the server by role.
    Route::prefix('admin')->name('admin.')->group(function () {
        $adminRoles = 'role:super_administrator,finance_administrator,game_administrator,support_administrator';

        Route::middleware($adminRoles)->group(function () {
            Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

            Route::get('/players', [AdminPlayer::class, 'index'])->name('players.index');
            Route::get('/players/{user}', [AdminPlayer::class, 'show'])->name('players.show');
            Route::post('/players/{user}/suspend', [AdminPlayer::class, 'suspend'])
                ->middleware('role:super_administrator')->name('players.suspend');
            Route::post('/players/{user}/reactivate', [AdminPlayer::class, 'reactivate'])
                ->middleware('role:super_administrator')->name('players.reactivate');

            Route::get('/rooms', [AdminRoom::class, 'index'])
                ->middleware('role:super_administrator,game_administrator')->name('rooms.index');
            Route::post('/rooms', [AdminRoom::class, 'store'])
                ->middleware('role:super_administrator,game_administrator')->name('rooms.store');
            Route::patch('/rooms/{room}', [AdminRoom::class, 'update'])
                ->middleware('role:super_administrator,game_administrator')->name('rooms.update');

            Route::get('/games', [AdminGame::class, 'index'])->name('games.index');
            Route::get('/games/{game}', [AdminGame::class, 'show'])->name('games.show');

            Route::get('/finance', [AdminFinance::class, 'index'])
                ->middleware('role:super_administrator,finance_administrator')->name('finance.index');
            Route::get('/ledger', [AdminFinance::class, 'ledger'])
                ->middleware('role:super_administrator,finance_administrator')->name('ledger.index');
            Route::post('/deposits/{deposit}/approve', [AdminFinance::class, 'approveDeposit'])
                ->middleware('role:super_administrator,finance_administrator')->name('deposits.approve');
            Route::post('/deposits/{deposit}/reject', [AdminFinance::class, 'rejectDeposit'])
                ->middleware('role:super_administrator,finance_administrator')->name('deposits.reject');
            Route::post('/withdrawals/{withdrawal}/approve', [AdminFinance::class, 'approveWithdrawal'])
                ->middleware('role:super_administrator,finance_administrator')->name('withdrawals.approve');
            Route::post('/withdrawals/{withdrawal}/reject', [AdminFinance::class, 'rejectWithdrawal'])
                ->middleware('role:super_administrator,finance_administrator')->name('withdrawals.reject');
            Route::post('/withdrawals/{withdrawal}/complete', [AdminFinance::class, 'completeWithdrawal'])
                ->middleware('role:super_administrator,finance_administrator')->name('withdrawals.complete');

            Route::get('/settings', [AdminSettings::class, 'index'])
                ->middleware('role:super_administrator')->name('settings.index');
            Route::patch('/settings', [AdminSettings::class, 'update'])
                ->middleware('role:super_administrator')->name('settings.update');
            Route::post('/settings/flags', [AdminSettings::class, 'updateFlags'])
                ->middleware('role:super_administrator')->name('settings.flags');
        });
    });

    // Starter CRUD user management, restricted to super administrators now that roles exist.
    Route::resource('crud', CRUD::class)
        ->middleware('role:super_administrator')
        ->names('crud');

    // Logout.
    Route::post('/logout', [Login::class, 'logout'])->name('logout');
});

// Anything unmatched renders the Inertia 404 page.
Route::fallback(function () {
    return Inertia::render('Errors/NotFound')->toResponse(request())->setStatusCode(404);
});
