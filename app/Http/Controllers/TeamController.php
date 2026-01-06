<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Team;

class TeamController extends Controller
{
    /**
     * Show the form for creating a new team.
     */
    public function create()
    {
        return Inertia::render('teams/CreateTeam');
    }

    /**
     * Store a newly created team.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
            'leader_id' => 'required|exists:members,adh_id',
        ]);
        return redirect()->route('home')->with('success', 'Team created successfully!');
    }
}
