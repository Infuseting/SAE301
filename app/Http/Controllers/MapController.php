<?php
namespace App\Http\Controllers;

use Inertia\Response;
use Inertia\Inertia;

class MapController extends Controller
{
   
    public function index():Response
    {

        return Inertia::render('map/map');
    }
}