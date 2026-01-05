<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index(Request $request)
    {

        $query = Activity::with('causer');

        $paginated = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Logs', [
            'logs' => $paginated->toArray(),
        ]);
    }
}