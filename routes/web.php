<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LeaderboardController as AdminLeaderboardController;
use App\Http\Controllers\TeamAgeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MyLeaderboardController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Public leaderboard page
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Welcome');
    })->name('dashboard');

    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile', [App\Http\Controllers\PublicProfileController::class, 'myProfile'])->name('profile.index');
    Route::get('/profile/{user}', [App\Http\Controllers\PublicProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/complete', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/user/set-password', [App\Http\Controllers\SetPasswordController::class, 'store'])->name('password.set');

    // My leaderboard - accessible to all authenticated users
    Route::get('/my-leaderboard', [MyLeaderboardController::class, 'index'])->name('my-leaderboard.index');

    // Team age validation page
    Route::get('/team/age-validation', [TeamAgeController::class, 'index'])->name('team.age-validation');
});

Route::middleware(['auth', 'verified', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    // dashboard
    Route::get('/', [AdminController::class, 'index'])->name('dashboard')->middleware('can:access-admin');

    // users
    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('can:view users');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:edit users');
    Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle')->middleware('can:edit users');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('can:delete users');

    // role assignment
    Route::get('/roles', [UserController::class, 'getRoles'])->name('roles.index')->middleware('can:grant role');
    Route::post('/users/{user}/role', [UserController::class, 'assignRole'])->name('users.assignRole')->middleware('can:grant role');
    Route::delete('/users/{user}/role', [UserController::class, 'removeRole'])->name('users.removeRole')->middleware('can:grant role');

    // logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index')->middleware('can:view logs');

    // leaderboard management
    Route::get('/leaderboard', [AdminLeaderboardController::class, 'index'])->name('leaderboard.index')->middleware('can:view users');
    Route::post('/leaderboard/import', [AdminLeaderboardController::class, 'import'])->name('leaderboard.import')->middleware('can:edit users');
    Route::get('/leaderboard/{raceId}/results', [AdminLeaderboardController::class, 'results'])->name('leaderboard.results')->middleware('can:view users');
    Route::delete('/leaderboard/results/{resultId}', [AdminLeaderboardController::class, 'destroy'])->name('leaderboard.destroy')->middleware('can:delete users');
});

require __DIR__ . '/auth.php';

Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\SocialiteController::class, 'redirect'])->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [\App\Http\Controllers\SocialiteController::class, 'callback'])->name('socialite.callback');

// Language switcher
Route::get('/lang/{locale}', function ($locale) {
    $available = ['en', 'es', 'fr'];
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
