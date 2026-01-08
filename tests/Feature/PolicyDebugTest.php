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

    /** @test */
    public function debug_club_policy_authorization()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create responsable-club with valid licence
        $member = Member::factory()->create([
            'adh_license' => '123456',
            'adh_end_validity' => now()->addYear(),
        ]);
        
        $responsableClub = User::factory()->create(['adh_id' => $member->adh_id]);
        $responsableClub->assignRole('responsable-club');

        // Create club owned by different user
        $club = Club::factory()->create();

        // Debug output
        dump("User ID: {$responsableClub->id}");
        dump("Club created_by: {$club->created_by}");
        dump("User has role responsable-club: " . ($responsableClub->hasRole('responsable-club') ? 'YES' : 'NO'));
        dump("User has permission edit-own-club: " . ($responsableClub->hasPermissionTo('edit-own-club') ? 'YES' : 'NO'));
        dump("Club hasManager(user): " . ($club->hasManager($responsableClub) ? 'YES' : 'NO'));
        dump("User can('update', club): " . ($responsableClub->can('update', $club) ? 'YES' : 'NO'));
        dump("Gate allows: " . (Gate::forUser($responsableClub)->allows('update', $club) ? 'YES' : 'NO'));

        // The assertion we EXPECT to pass
        $this->assertFalse($responsableClub->can('update', $club), 
            'ResponsableClub should NOT be able to update club they do not own or manage');
    }
}
