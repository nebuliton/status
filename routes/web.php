<?php

use App\Http\Controllers\StatusShareController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'status')->name('home');
Route::view('/status', 'status')->name('status.index');
Route::view('/impressum', 'impressum')->name('impressum');
Route::get('/status/card.svg', [StatusShareController::class, 'overviewImage'])->name('status.overview.image');
Route::get('/status/{service:slug}/card.svg', [StatusShareController::class, 'serviceImage'])->name('status.service.image');
Route::get('/status/{service:slug}', [StatusShareController::class, 'service'])->name('status.service.show');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';
