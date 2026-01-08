<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Inertia\Response;
use Inertia\Inertia;
use App\Models\Raid;
use App\Models\AgeCategory;

class WelcomeController extends Controller
{
   
    public function index(Request $request): Response
    {

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

        // Get all age categories for the search filter
        $ageCategories = AgeCategory::all();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'upcomingRaids' => $upcomingRaids,
            'ageCategories' => $ageCategories,
        ]);
    }
}