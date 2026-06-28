<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GmailAccountController;
use App\Http\Controllers\GmailSyncController;
use App\Http\Controllers\GoogleController;

Route::redirect('/', '/dashboard');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::get('/emails/{email}', [DashboardController::class, 'show'])
    ->name('emails.show');

/*
|--------------------------------------------------------------------------
| Gmail OAuth
|--------------------------------------------------------------------------
*/
Route::get('/auth/google', [GoogleController::class, 'redirect'])
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
    ->name('google.callback');

Route::post(
    '/sync-emails',
    [GmailSyncController::class, 'sync']
)->name('emails.sync');
