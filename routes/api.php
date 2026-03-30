<?php

use App\Http\Controllers\Api\Admin\ApplicationUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->prefix('admin')
    ->group(function (): void {
        Route::get('updates', [ApplicationUpdateController::class, 'show']);
        Route::post('updates/run', [ApplicationUpdateController::class, 'run'])->middleware('throttle:sensitive');
        Route::patch('updates/preferences', [ApplicationUpdateController::class, 'preferences']);
        Route::get('updates/runs/{runId}', [ApplicationUpdateController::class, 'runShow']);
    });
