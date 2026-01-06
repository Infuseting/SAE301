<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    /**
     * Display activity logs with pagination.
     * Accepts page parameter via POST to avoid URL parameters.
     */
    public function index(Request $request)
    {
        // Get page from POST body or fallback to 1
        $page = $request->input('page', 1);

        $query = Activity::with('causer');

        // Manual pagination to avoid query string
        $logs = $query->latest()->paginate(15, ['*'], 'page', $page);

        return Inertia::render('Admin/Logs', [
            'logs' => $logs,
            'filters' => [
                'page' => $page,
            ],
        ]);
    }
}