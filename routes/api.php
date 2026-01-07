<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamAgeController;

Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::get('/users/search', [\App\Http\Controllers\Api\UserController::class, 'search']);

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

/*
|--------------------------------------------------------------------------
| Club API Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->as('api.')->group(function () {
    // Club CRUD
    Route::apiResource('clubs', \App\Http\Controllers\ClubController::class);

    // Club member management
    Route::post('/clubs/{club}/join', [\App\Http\Controllers\ClubMemberController::class, 'requestJoin']);
    Route::post('/clubs/{club}/leave', [\App\Http\Controllers\ClubMemberController::class, 'leave']);
    Route::post('/clubs/{club}/members/{user}/approve', [\App\Http\Controllers\ClubMemberController::class, 'approveJoin']);
    Route::post('/clubs/{club}/members/{user}/reject', [\App\Http\Controllers\ClubMemberController::class, 'rejectJoin']);
    Route::delete('/clubs/{club}/members/{user}', [\App\Http\Controllers\ClubMemberController::class, 'removeMember']);

    // Admin club approval
    Route::middleware('permission:accept-club')->prefix('admin')->group(function () {
        Route::get('/clubs/pending', [\App\Http\Controllers\Admin\ClubApprovalController::class, 'index']);
        Route::post('/clubs/{club}/approve', [\App\Http\Controllers\Admin\ClubApprovalController::class, 'approve']);
        Route::post('/clubs/{club}/reject', [\App\Http\Controllers\Admin\ClubApprovalController::class, 'reject']);
    });
});
