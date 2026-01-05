<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;

class UserController extends Controller
{
    // List users with pagination and optional search
    public function index(Request $request)
    {
        $q = $request->input('q');

        $query = User::query();

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->toArray(),
        ]);
    }

    // Update user (name, email, active)
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'active' => 'sometimes|boolean',
        ]);

        $before = $user->only(['name', 'email', 'active']);

        $user->fill($data);
        $user->save();

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'level' => 'info',
                'action' => 'ADMIN_UPDATED_USER',
                'content' => ['before' => $before, 'after' => $user->only(['name', 'email', 'active'])],
                'ip' => $request->ip(),
            ])
            ->log('ADMIN_UPDATED_USER');

        return redirect()->back();
    }

    // Disable / enable user
    public function toggle(Request $request, User $user)
    {
        $user->active = !$user->active;
        $user->save();

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'level' => 'notice',
                'action' => 'ADMIN_TOGGLED_ACTIVE_USER',
                'content' => ['id' => $user->id, 'active' => $user->active],
                'ip' => $request->ip(),
            ])
            ->log('ADMIN_TOGGLED_ACTIVE_USER');
        return redirect()->back();
    }

    // Delete user
    public function destroy(Request $request, User $user)
    {
        $info = $user->only(['id', 'name', 'email']);

        $user->delete();

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'level' => 'critical',
                'action' => 'ADMIN_DELETED_USER',
                'content' => $info,
                'ip' => $request->ip(),
            ])
            ->log('ADMIN_DELETED_USER');
        return redirect()->back();
    }
}
