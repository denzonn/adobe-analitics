<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GmailSyncController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root redirect
|--------------------------------------------------------------------------
|
| Arahkan root ke dashboard (kalau sudah login) atau ke halaman login.
| Karena route group auth dipasang di bawah, middleware 'auth' akan
| men-redirect guest ke /login secara otomatis, sehingga di sini cukup
| arahkan '/'.
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authenticated area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/emails/{email}', [DashboardController::class, 'show'])
        ->name('emails.show');

    // Gmail OAuth
    Route::get('/auth/google', [GoogleController::class, 'redirect'])
        ->name('google.redirect');

    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->name('google.callback');

    Route::post('/sync-emails', [GmailSyncController::class, 'sync'])
        ->name('emails.sync');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
