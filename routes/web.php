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
use App\Http\Controllers\RaidController;
use App\Models\Raid;

Route::get('/', function () {
    $upcomingRaids = Raid::with('club')
        ->where('raid_date_start', '>=', now())
        ->orderBy('raid_date_start', 'asc')
        ->take(3)
        ->get()
        ->map(function ($raid) {
            return [
                'id' => $raid->raid_id,
                'title' => $raid->raid_name,
                'date' => $raid->raid_date_start ? \Carbon\Carbon::parse($raid->raid_date_start)->format('d M Y') : '',
                'location' => trim(($raid->raid_city ?? '') . ', ' . ($raid->raid_country ?? ''), ', '),
                'type' => 'Raid',
                'image' => $raid->raid_image ?? 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            ];
        });

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'upcomingRaids' => $upcomingRaids,
    ]);
})->name('home');

// Race routes
Route::get('/race/{id}', [VisuRaceController::class, 'show'])->name('races.show');
Route::get('/map', [App\Http\Controllers\MapController::class, 'index'])->name('map.index');

// Raids public routes (no auth required)
Route::get('/raids', [RaidController::class, 'index'])->name('raids.index');
Route::get('/raids/{raid}', [RaidController::class, 'show'])->name('raids.show')->whereNumber('raid');


//myRace
Route::get('/my-race', [App\Http\Controllers\Race\MyRaceController::class, 'index'])->name('myrace.index');


Route::middleware('auth')->group(function () {
    // Race management (requires auth, authorization handled by controller/policy)
    Route::get('/new-race', [NewRaceController::class, 'show'])->name('races.create');
    Route::post('/new-race', [NewRaceController::class, 'store'])->name('races.store');
    Route::get('/race/{id}/edit', [NewRaceController::class, 'show'])->name('races.edit'); // Placeholder

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

    // Clubs routes
    Route::resource('clubs', App\Http\Controllers\ClubController::class);

    // Club routes and club leader role
    Route::middleware('club_leader')->group(function () {
        // Raids routes (only club leaders can manage raids)
        Route::get('/raids/create', [RaidController::class, 'create'])->name('raids.create');
        Route::post('/raids', [RaidController::class, 'store'])->name('raids.store');
        Route::get('/raids/{raid}/edit', [RaidController::class, 'edit'])->name('raids.edit');
        Route::put('/raids/{raid}', [RaidController::class, 'update'])->name('raids.update');
        Route::delete('/raids/{raid}', [RaidController::class, 'destroy'])->name('raids.destroy');
    });

    // Club member management (authorization handled in controller)
    Route::post('/clubs/{club}/join', [App\Http\Controllers\ClubMemberController::class, 'requestJoin'])->name('clubs.join');
    Route::post('/clubs/{club}/leave', [App\Http\Controllers\ClubMemberController::class, 'leave'])->name('clubs.leave');
    Route::post('/clubs/{club}/members/{user}/approve', [App\Http\Controllers\ClubMemberController::class, 'approveJoin'])->name('clubs.members.approve');
    Route::post('/clubs/{club}/members/{user}/reject', [App\Http\Controllers\ClubMemberController::class, 'rejectJoin'])->name('clubs.members.reject');
    Route::delete('/clubs/{club}/members/{user}', [App\Http\Controllers\ClubMemberController::class, 'removeMember'])->name('clubs.members.remove');

    // Licence and PPS management
    Route::post('/licence', [App\Http\Controllers\LicenceController::class, 'storeLicence'])->name('licence.store');
    Route::post('/pps', [App\Http\Controllers\LicenceController::class, 'storePpsCode'])->name('pps.store');
    Route::get('/credentials/check', [App\Http\Controllers\LicenceController::class, 'checkCredentials'])->name('credentials.check');

    // Race registration
    Route::get('/races/{race}/registration/check', [App\Http\Controllers\RaceRegistrationController::class, 'checkEligibility'])->name('race.registration.check');
    Route::post('/races/{race}/register', [App\Http\Controllers\RaceRegistrationController::class, 'register'])->name('race.register');
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

    // Club approval
    Route::get('/clubs/pending', [App\Http\Controllers\Admin\ClubApprovalController::class, 'index'])->name('clubs.pending')->middleware('can:accept-club');
    Route::post('/clubs/{club}/approve', [App\Http\Controllers\Admin\ClubApprovalController::class, 'approve'])->name('clubs.approve')->middleware('can:accept-club');
    Route::post('/clubs/{club}/reject', [App\Http\Controllers\Admin\ClubApprovalController::class, 'reject'])->name('clubs.reject')->middleware('can:accept-club');
});

require __DIR__ . '/auth.php';

Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\SocialiteController::class, 'redirect'])->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [\App\Http\Controllers\SocialiteController::class, 'callback'])->name('socialite.callback');

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
