<?php

use App\Http\Controllers\Auth\Login;
use App\Http\Controllers\CRUD;
use App\Http\Controllers\Dashboard;
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
    // Dashboard (the eCommerce style overview page).
    Route::get('/dashboard', [Dashboard::class, 'index'])->name('dashboard');

    // CRUD users pages.
    Route::resource('crud', CRUD::class)->names('crud');

    // Logout.
    Route::post('/logout', [Login::class, 'logout'])->name('logout');
});

// Anything unmatched renders the Inertia 404 page.
Route::fallback(function () {
    return Inertia::render('Errors/NotFound')->toResponse(request())->setStatusCode(404);
});
