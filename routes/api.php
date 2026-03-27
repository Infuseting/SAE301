<?php

use App\Http\Controllers\Api\LeaderboardApiController;
use App\Http\Controllers\Api\RaceApiController;
use Illuminate\Support\Facades\Route;

// Public endpoints (no authentication required)
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::get('/clubs', [\App\Http\Controllers\Club\ClubController::class, 'index']);
Route::get('/clubs/{club}', [\App\Http\Controllers\Club\ClubController::class, 'show'])
    ->middleware(\App\Http\Middleware\AttemptSanctumAuthentication::class);

// Socialite authentication routes
Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\Auth\SocialiteController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Auth\SocialiteController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| Public Leaderboard Routes
|--------------------------------------------------------------------------
*/
Route::prefix('leaderboard')->group(function () {
    Route::get('/races', [LeaderboardApiController::class, 'races']);
    Route::get('/{raceId}/individual', [LeaderboardApiController::class, 'individual']);
    Route::get('/{raceId}/teams', [LeaderboardApiController::class, 'teams']);
    Route::get('/{raceId}/user/{userId}', [LeaderboardApiController::class, 'userResult']);
});

/*
|--------------------------------------------------------------------------
| Public Raid Routes
|--------------------------------------------------------------------------
*/
Route::get('/raids', [\App\Http\Controllers\Raid\RaidController::class, 'index']);
Route::get('/raids/{raid:raid_id}', [\App\Http\Controllers\Raid\RaidController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Public Race Routes
|--------------------------------------------------------------------------
*/
Route::get('/races', [RaceApiController::class, 'index']);
Route::get('/races/{id}', [RaceApiController::class, 'show']);



Route::middleware('auth:sanctum')->as('api.')->group(function () {

    // User / Profile endpoints - accessible to authenticated users
    Route::get('/user', [\App\Http\Controllers\Profile\ProfileController::class, 'index']);
    Route::patch('/user', [\App\Http\Controllers\Profile\ProfileController::class, 'update']);
    Route::delete('/user', [\App\Http\Controllers\Profile\ProfileController::class, 'destroy']);
    Route::put('/user/set-password', [\App\Http\Controllers\Auth\SetPasswordController::class, 'store']);
    Route::get('/user/managed-clubs', [\App\Http\Controllers\Club\ClubController::class, 'managed']);
    Route::get('/user/managed-races', [\App\Http\Controllers\Api\RaceManagementController::class, 'index']);
    Route::post('/user/profile/complete', [\App\Http\Controllers\Profile\ProfileController::class, 'complete']);
    Route::get('/users/search', [\App\Http\Controllers\Api\UsersController::class, 'search']);
    Route::get('/users/adherents', [\App\Http\Controllers\Api\UsersController::class, 'adherents']);

    // Club operations - requires auth, authorization in controller
    Route::apiResource('clubs', \App\Http\Controllers\Club\ClubController::class)->except(['index', 'show']);

    // Club member management
    Route::post('/clubs/{club}/join', [\App\Http\Controllers\Club\ClubMemberController::class, 'requestJoin']);
    Route::post('/clubs/{club}/leave', [\App\Http\Controllers\Club\ClubMemberController::class, 'leave']);
    Route::post('/clubs/{club}/members/{user}/approve', [\App\Http\Controllers\Club\ClubMemberController::class, 'approveJoin']);
    Route::post('/clubs/{club}/members/{user}/reject', [\App\Http\Controllers\Club\ClubMemberController::class, 'rejectJoin']);
    Route::delete('/clubs/{club}/members/{user}', [\App\Http\Controllers\Club\ClubMemberController::class, 'removeMember']);

    // Club member promotion/demotion
    Route::post('/clubs/{club}/members/{user}/promote', [\App\Http\Controllers\Club\ClubMemberController::class, 'promoteToManager']);
    Route::post('/clubs/{club}/members/{user}/demote', [\App\Http\Controllers\Club\ClubMemberController::class, 'demoteFromManager']);

    // Admin club approval
    Route::middleware('permission:accept-club')->prefix('admin')->group(function () {
        Route::get('/clubs/pending', [\App\Http\Controllers\Admin\ClubApprovalController::class, 'index']);
        Route::post('/clubs/{club}/approve', [\App\Http\Controllers\Admin\ClubApprovalController::class, 'approve']);
        Route::post('/clubs/{club}/reject', [\App\Http\Controllers\Admin\ClubApprovalController::class, 'reject']);
    });

    // Team age validation endpoints
    Route::prefix('teams')->group(function () {
        // Route : /api/teams/age-validation ??
        Route::get('/age-thresholds', [\App\Http\Controllers\Team\TeamAgeController::class, 'getThresholds']);
        Route::post('/validate-ages', [\App\Http\Controllers\Team\TeamAgeController::class, 'validateAges']);
        Route::post('/validate-birthdates', [\App\Http\Controllers\Team\TeamAgeController::class, 'validateBirthdates']);
        Route::post('/check-participant', [\App\Http\Controllers\Team\TeamAgeController::class, 'checkParticipant']);
    });

    // Teams CRUD - requires auth, authorization in controller
    Route::prefix('teams')->group(function () {
        //Route::get('/', [\App\Http\Controllers\Team\TeamController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Team\TeamController::class, 'store']);
        Route::get('/{team}', [\App\Http\Controllers\Team\TeamController::class, 'show']);
        Route::put('/{team}', [\App\Http\Controllers\Team\TeamManagementController::class, 'update']);
        Route::delete('/{team}', [\App\Http\Controllers\Team\TeamManagementController::class, 'destroy']);

        // Team invitations
        Route::post('/{team}/invite', [\App\Http\Controllers\Team\TeamController::class, 'inviteByEmail']);
        Route::post('/{team}/remove-member', [\App\Http\Controllers\Team\TeamManagementController::class, 'removeMember']);

        // Team management
        Route::get('/management', [\App\Http\Controllers\Team\TeamManagementController::class, 'index']);

        // Registration and tickets
        Route::get('/{team}/registration/{registration}/ticket', [\App\Http\Controllers\Team\TeamController::class, 'showRegistrationTicket']);
        Route::get('/{team}/registration/{registration}/qr-code', [\App\Http\Controllers\Team\TeamController::class, 'downloadQrCode']);
    });

    // Team invitations
    Route::post('/teams/invitations/{token}/accept', [\App\Http\Controllers\Team\TeamController::class, 'acceptInvitation'])->name('teams.invitations.accept');
    Route::get('/teams/invitations/{token}', [\App\Http\Controllers\Team\TeamController::class, 'showAcceptInvitation'])->name('teams.invitations.show');

    // Race registration - requires valid licence
    Route::middleware('manager_licence')->group(function () {
        Route::get('/races/{raceId}/registration/check', [\App\Http\Controllers\Race\RaceRegistrationController::class, 'checkEligibility']);
        Route::post('/races/{raceId}/register', [\App\Http\Controllers\Race\RaceRegistrationController::class, 'register']);
    });

    // Race participants management (per registration)
    Route::prefix('registrations')->group(function () {
        Route::get('/{registration}/runners', [\App\Http\Controllers\Team\TeamRunnerController::class, 'index']);
        Route::post('/{registration}/runners', [\App\Http\Controllers\Team\TeamRunnerController::class, 'store']);
    });

    // Race participants CRUD
    Route::prefix('races/participants')->group(function () {
        Route::put('/{participant}', [\App\Http\Controllers\Team\TeamRunnerController::class, 'update']);
        Route::delete('/{participant}', [\App\Http\Controllers\Team\TeamRunnerController::class, 'destroy']);
        Route::post('/{participant}/verify-pps', [\App\Http\Controllers\Team\TeamRunnerController::class, 'verifyPps']);
    });

    // Race management - requires race manager role
    Route::middleware('role:responsable-course|gestionnaire-raid|admin')->group(function () {
        Route::prefix('races')->group(function () {
            Route::get('/{race}/start-list', [\App\Http\Controllers\Race\RaceController::class, 'generateStartList']);
            Route::post('/{race}/check-in', [\App\Http\Controllers\Race\RaceController::class, 'checkIn']);
            Route::post('/{race}/toggle-presence', [\App\Http\Controllers\Race\RaceController::class, 'togglePresence']);
            Route::get('/{race}/team-members/{registration}', [\App\Http\Controllers\Race\RaceController::class, 'getTeamMembers']);
        });
    });

    // Raid management - requires raid manager role
    Route::middleware('role:gestionnaire-raid|responsable-club|admin')->group(function () {
        Route::prefix('raids')->group(function () {
            Route::post('/', [\App\Http\Controllers\Raid\RaidController::class, 'store']);
            Route::put('/{raid}', [\App\Http\Controllers\Raid\RaidController::class, 'update']);
            Route::delete('/{raid}', [\App\Http\Controllers\Raid\RaidController::class, 'destroy']);
            Route::get('/{raid}/start-list', [\App\Http\Controllers\Raid\RaidController::class, 'generateStartList']);
            Route::post('/{raid}/check-in', [\App\Http\Controllers\Raid\RaidController::class, 'checkIn']);
        });
    });

    // Race management API
    Route::middleware('role:responsable-course|gestionnaire-raid|admin')->group(function () {
        Route::post('/races/create', [\App\Http\Controllers\Race\RaceController::class, 'store']);
        Route::get('/races/{race}/participants', [\App\Http\Controllers\Api\RaceManagementController::class, 'participants']);
    });
    Route::patch('/races/registrations/{registration}/validate-docs', [\App\Http\Controllers\Api\RaceManagementController::class, 'validateDocuments']);
});
