<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamAgeController;

Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);

Route::get('/user', \App\Http\Controllers\Api\UserController::class)->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Team Age Validation Routes
|--------------------------------------------------------------------------
*/
Route::prefix('team')->group(function () {
    Route::get('/age-thresholds', [TeamAgeController::class, 'getThresholds']);
    Route::post('/validate-ages', [TeamAgeController::class, 'validateAges']);
    Route::post('/validate-birthdates', [TeamAgeController::class, 'validateBirthdates']);
    Route::post('/check-participant', [TeamAgeController::class, 'checkParticipant']);
});
