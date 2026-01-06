<?php
namespace App\Http\Controllers;

use Inertia\Response;
use Inertia\Inertia;
use App\Models\Raids;

class MapController extends Controller
{
    /**
     * Display the map page with all raids.
     * 
     * @return Response
     */
    public function index(): Response
    {
        // Récupère tous les raids de la base de données
        $raids = Raids::all();

        return Inertia::render('map/map', [
            'raids' => $raids,
        ]);
    }
}