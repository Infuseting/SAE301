<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LicenceController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClubApprovalController;
use App\Http\Controllers\Admin\LeaderboardController as AdminLeaderboardController;
use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Club\ClubController;
use App\Http\Controllers\Club\ClubMemberController;
use App\Http\Controllers\Leaderboard\LeaderboardController;
use App\Http\Controllers\Leaderboard\MyLeaderboardController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\PublicProfileController;
use App\Http\Controllers\Race\RaceRegistrationController;
use App\Http\Controllers\Race\MyRaceController;
use App\Http\Controllers\Race\RaceController;
use App\Http\Controllers\Race\VisuRaceController;
use App\Http\Controllers\Raid\RaidController;
use App\Http\Controllers\Team\TeamController;
use App\Http\Controllers\Team\TeamAgeController;
use App\Models\Raid;


Route::get('/', [WelcomeController::class, 'index'])->name('home');

// Race routes
Route::get('/races', [VisuRaceController::class, 'index'])->name('races.index');
Route::get('/race/{id}', [VisuRaceController::class, 'show'])->name('races.show');
Route::get('/map', [MapController::class, 'index'])->name('map.index');

// Raids public routes (no auth required)
Route::get('/raids', [RaidController::class, 'index'])->name('raids.index');
Route::get('/raids/{raid}', [RaidController::class, 'show'])->name('raids.show')->whereNumber('raid');

//myRaid
Route::get('/my-raid', [App\Http\Controllers\Raid\MyRaidController::class, 'index'])->name('myraid.index');



//myRace
Route::get('/my-race', [MyRaceController::class, 'index'])->name('myrace.index');

// Public leaderboard page
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
Route::get('/leaderboard/export/{raceId}', [LeaderboardController::class, 'export'])->name('leaderboard.export');

// Accept invitation via token
Route::get('/invitations/accept/{token}', [TeamController::class, 'acceptInvitation'])->name('invitations.accept');

Route::middleware('auth')->group(function () {
    // Race management (requires auth, authorization handled by controller/policy)
    Route::get('/races/create', [RaceController::class, 'show'])->name('races.create');
    Route::post('/races/create', [RaceController::class, 'store'])->name('races.store');
    Route::get('/races/{id}/edit', [RaceController::class, 'edit'])->name('races.edit');
    Route::put('/races/{id}', [RaceController::class, 'update'])->name('races.update');
    Route::delete('/races/{id}', [RaceController::class, 'destroy'])->name('races.destroy');
    Route::get('/dashboard', function () {
        return Inertia::render('Welcome');
    })->name('dashboard');

    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile', [PublicProfileController::class, 'myProfile'])->name('profile.index');
    Route::get('/profile/{user}', [PublicProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/complete', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/user/set-password', [SetPasswordController::class, 'store'])->name('password.set');

    // My leaderboard - accessible to all authenticated users
    Route::get('/my-leaderboard', [MyLeaderboardController::class, 'index'])->name('my-leaderboard.index');

    // Team age validation page
    Route::get('/team/age-validation', [TeamAgeController::class, 'index'])->name('team.age-validation');

    // Clubs routes
    Route::resource('clubs', ClubController::class);

    // Club routes and club leader role
    Route::middleware('club_leader')->group(function () {
        // Raids routes (only club leaders can manage raids)
        Route::get('/raids/create', [RaidController::class, 'create'])->name('raids.create');
        Route::post('/raids', [RaidController::class, 'store'])->name('raids.store');
        Route::get('/raids/{raid}/edit', [RaidController::class, 'edit'])->name('raids.edit');
        Route::match(['put', 'patch'], '/raids/{raid}', [RaidController::class, 'update'])->name('raids.update');
        Route::delete('/raids/{raid}', [RaidController::class, 'destroy'])->name('raids.destroy');
    });

    // Club member management (authorization handled in controller)
    Route::post('/clubs/{club}/join', [ClubMemberController::class, 'requestJoin'])->name('clubs.join');
    Route::post('/clubs/{club}/leave', [ClubMemberController::class, 'leave'])->name('clubs.leave');
    Route::post('/clubs/{club}/members/{user}/approve', [ClubMemberController::class, 'approveJoin'])->name('clubs.members.approve');
    Route::post('/clubs/{club}/members/{user}/reject', [ClubMemberController::class, 'rejectJoin'])->name('clubs.members.reject');
    Route::delete('/clubs/{club}/members/{user}', [ClubMemberController::class, 'removeMember'])->name('clubs.members.remove');
    Route::post('/clubs/{club}/members/{user}/promote', [ClubMemberController::class, 'promoteToManager'])->name('clubs.members.promote');
    Route::post('/clubs/{club}/members/{user}/demote', [ClubMemberController::class, 'demoteFromManager'])->name('clubs.members.demote');
    // Licence and PPS management
    Route::post('/licence', [LicenceController::class, 'storeLicence'])->name('licence.store');
    Route::post('/pps', [LicenceController::class, 'storePpsCode'])->name('pps.store');
    Route::get('/credentials/check', [LicenceController::class, 'checkCredentials'])->name('credentials.check');

    // Race registration
    Route::get('/races/{race}/registration/check', [RaceRegistrationController::class, 'checkEligibility'])->name('race.registration.check');
    Route::post('/races/{race}/register', [RaceRegistrationController::class, 'register'])->name('race.register');
    
    // Team creation routes
    Route::get('/createTeam', [TeamController::class, 'create'])->name('team.create');
    Route::post('/createTeam', [TeamController::class, 'store'])->name('team.store');
    // Show team details
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show')->whereNumber('team');
    Route::post('/teams/{team}/invite-email', [TeamController::class, 'inviteByEmail'])->name('teams.invite-email');
    Route::post('/teams/{team}/invite/{user}', [TeamController::class, 'inviteByEmail'])->name('teams.invite-user');
});

Route::middleware(['auth', 'verified', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    // dashboard
    Route::get('/', [AdminController::class, 'index'])->name('dashboard')->middleware('can:access-admin');

    // users
    Route::match(['get', 'post'], '/users', [UserController::class, 'index'])->name('users.index')->middleware('can:view users');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:edit users');
    Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle')->middleware('can:edit users');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('can:delete users');

    // role assignment
    Route::get('/roles', [UserController::class, 'getRoles'])->name('roles.index')->middleware('can:grant role');
    Route::post('/users/{user}/role', [UserController::class, 'assignRole'])->name('users.assignRole')->middleware('can:grant role');
    Route::delete('/users/{user}/role', [UserController::class, 'removeRole'])->name('users.removeRole')->middleware('can:grant role');



    // logs
    Route::match(['get', 'post'], '/logs', [LogController::class, 'index'])->name('logs.index')->middleware('can:view logs');

    // leaderboard management
    Route::get('/leaderboard', [AdminLeaderboardController::class, 'index'])->name('leaderboard.index')->middleware('can:view users');
    Route::post('/leaderboard/import', [AdminLeaderboardController::class, 'import'])->name('leaderboard.import')->middleware('can:edit users');
    Route::get('/leaderboard/export/{raceId}', [AdminLeaderboardController::class, 'export'])->name('leaderboard.export')->middleware('can:view users');
    Route::get('/leaderboard/{raceId}/results', [AdminLeaderboardController::class, 'results'])->name('leaderboard.results')->middleware('can:view users');
    Route::delete('/leaderboard/results/{resultId}', [AdminLeaderboardController::class, 'destroy'])->name('leaderboard.destroy')->middleware('can:delete users');

    // Club approval
    Route::get('/clubs/pending', [ClubApprovalController::class, 'index'])->name('clubs.pending')->middleware('can:accept-club');
    Route::post('/clubs/{club}/approve', [ClubApprovalController::class, 'approve'])->name('clubs.approve')->middleware('can:accept-club');
    Route::post('/clubs/{club}/reject', [ClubApprovalController::class, 'reject'])->name('clubs.reject')->middleware('can:accept-club');
});

require __DIR__ . '/auth.php';

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('socialite.callback');

// Language switcher
Route::get('/lang/{locale}', function ($locale) {
    $available = ['en', 'es', 'fr', 'de'];
    if (!in_array($locale, $available)) {
        $locale = config('app.locale');
    }

    session(['locale' => $locale]);

    // If this is an Inertia request, force a full-location redirect so the client reloads with the new locale
    if (request()->header('X-Inertia')) {
        return Inertia::location(url()->previous());
    }

    return redirect()->back();
})->name('lang.switch');
