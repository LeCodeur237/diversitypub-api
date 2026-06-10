<?php

use App\Http\Controllers\KOGagnantController;
use Illuminate\Support\Facades\Route;

Route::prefix('ko-gagnant')->group(function () {
    Route::post('/submit', [KOGagnantController::class, 'submit']);
    Route::post('/claim', [KOGagnantController::class, 'claim']);
});