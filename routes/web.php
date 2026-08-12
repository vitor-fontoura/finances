<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::livewire('transactions', 'pages::transactions.index')->name('transactions');
        Route::livewire('schedules', 'pages::schedules.index')->name('schedules');
        Route::livewire('categories', 'pages::categories.index')->name('categories');
        Route::livewire('accounts', 'pages::accounts.index')->name('accounts');
        Route::livewire('imports', 'pages::imports.index')->name('imports');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';
