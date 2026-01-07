<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Spatie\Activitylog\Models\Activity;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'logs' => Activity::count(),
            'pendingClubs' => \App\Models\Club::where('is_approved', false)->count(),
        ];

        return inertia('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
