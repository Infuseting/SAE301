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
        $user->fill($data);

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
