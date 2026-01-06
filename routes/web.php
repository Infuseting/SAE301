<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserController;

use App\Http\Controllers\Race\NewRaceController;
use App\Http\Controllers\Race\VisuRaceController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

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

    Route::get('/new-race', [NewRaceController::class, 'show'])->name('race.show');
    Route::get('/race/{race}', [VisuRaceController::class, 'show'])->name('race.view');
});

Route::middleware(['auth', 'verified', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    // dashboard
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');

    // users
    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('can:view users');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:edit users');
    Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle')->middleware('can:edit users');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('can:delete users');

    

    // logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index')->middleware('can:view logs');
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



Route::get('race/{idRace}', [VisuRaceController::class, 'show'])->name('register');

