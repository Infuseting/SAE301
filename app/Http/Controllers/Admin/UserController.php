<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * List users with pagination and optional search.
     * Includes user roles for display.
     */
    public function index(Request $request)
    {
        $q = $request->input('q');

        $query = User::with('roles:id,name');

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Transform users to include role names as array
        $usersArray = $users->toArray();
        $usersArray['data'] = collect($usersArray['data'])->map(function ($user) {
            $user['role_names'] = collect($user['roles'] ?? [])->pluck('name')->toArray();
            return $user;
        })->toArray();

        return Inertia::render('Admin/Users/Index', [
            'users' => $usersArray,
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

    /**
     * Get all available roles for assignment.
     */
    public function getRoles(Request $request): \Illuminate\Http\JsonResponse
    {
        $roles = Role::all(['id', 'name']);

        return response()->json(['roles' => $roles]);
    }

    /**
     * Assign a role to a user (adds to existing roles).
     * Requires 'grant role' permission, and 'grant admin' for admin role.
     */
    public function assignRole(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $roleName = $data['role'];

        // Check if assigning admin role requires 'grant admin' permission
        if ($roleName === 'admin' && ! $request->user()->can('grant admin')) {
            abort(403, 'You do not have permission to grant the admin role.');
        }

        // Add the role (keeps existing roles)
        $user->assignRole($roleName);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties([
                'level' => 'warning',
                'action' => 'ADMIN_ASSIGNED_ROLE',
                'content' => ['user_id' => $user->id, 'role' => $roleName],
                'ip' => $request->ip(),
            ])
            ->log('ADMIN_ASSIGNED_ROLE');

        return redirect()->back();
    }

    /**
     * Remove a role from a user.
     * Requires 'grant role' permission, and 'grant admin' for admin role.
     */
    public function removeRole(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $roleName = $data['role'];

        // Check if removing admin role requires 'grant admin' permission
        if ($roleName === 'admin' && ! $request->user()->can('grant admin')) {
            abort(403, 'You do not have permission to remove the admin role.');
        }

        // Remove the role
        $user->removeRole($roleName);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties([
                'level' => 'warning',
                'action' => 'ADMIN_REMOVED_ROLE',
                'content' => ['user_id' => $user->id, 'role' => $roleName],
                'ip' => $request->ip(),
            ])
            ->log('ADMIN_REMOVED_ROLE');

        return redirect()->back();
    }
}
