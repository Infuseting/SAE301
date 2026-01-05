<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProfileService
{
    /**
     * Update the user's profile information.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function update(User $user, array $data): User
    {
        // Log user update
        $before = $user->only(array_keys($data));

        $user->fill($data);

        activity()
            ->causedBy($user)
            ->withProperties([
                'level' => 'info',
                'action' => 'USER_UPDATED',
                'content' => ['before' => $before, 'after' => $user->only(array_keys($data))],
                'ip' => request()->ip(),
            ])
            ->log('USER_UPDATED');

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }

    /**
     * Delete the user's account.
     *
     * @param User $user
     * @return void
     */
    public function deleteAccount(User $user): void
    {
        // Log user deletion
        activity()
            ->causedBy($user)
            ->withProperties([
                'level' => 'critical',
                'action' => 'USER_DELETED',
                'content' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
                'ip' => request()->ip(),
            ])
            ->log('USER_DELETED');

        Auth::logout();

        $user->delete();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
    /**
     * Set the user's password.
     *
     * @param User $user
     * @param string $password
     * @return void
     */
    public function setPassword(User $user, string $password): void
    {
        $user->forceFill([
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'password_is_set' => true,
        ])->save();
    }
}
