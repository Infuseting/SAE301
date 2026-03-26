<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_club_policy_authorization()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create responsable-club with valid license
        $member = Member::factory()->create([
            'adh_license' => '123456',
            'adh_end_validity' => now()->addYear(),
        ]);

        $responsableClub = User::factory()->create(['adh_id' => $member->adh_id]);
        $responsableClub->assignRole('responsable-club');

        // Create club owned by different user
        $club = Club::factory()->create();

        // The assertion we EXPECT to pass
        $this->assertFalse($responsableClub->can('update', $club),
            'ResponsableClub should NOT be able to update club they do not own or manage');
    }
}
