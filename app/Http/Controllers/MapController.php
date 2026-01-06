<?php
namespace App\Http\Controllers;

use Inertia\Response;
use Inertia\Inertia;

class MapController extends Controller
{
    /**
     * Display the map page.
     */
    public function index(): Response
    {
        return Inertia::render('map/map');
    }
}