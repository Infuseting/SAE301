<?php

namespace Tests\Feature\PermissionsRoles;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_assigned_role()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->can('view users'));
    }

    public function test_user_can_have_multiple_roles()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->assignRole('user');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('user'));
    }
}
