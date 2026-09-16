<?php

use App\Http\Controllers\KOGagnantController;
use App\Http\Controllers\RouletteParticipationController;
use Illuminate\Support\Facades\Route;

Route::post('/roulette/participate', [RouletteParticipationController::class, 'submit']);

Route::prefix('ko-gagnant')->group(function () {
    Route::post('/check', [KOGagnantController::class, 'check']);
    Route::post('/submit', [KOGagnantController::class, 'submit']);
    Route::post('/claim', [KOGagnantController::class, 'claim']);
});
