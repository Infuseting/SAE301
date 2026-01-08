<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\Member;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;

class DebugPolicyCommand extends Command
{
    protected $signature = 'debug:policy';
    protected $description = 'Debug ClubPolicy authorization';

    public function handle()
    {
        // Ensure roles/permissions exist
        \Artisan::call('migrate:fresh');
        \Artisan::call('db:seed');

        // Create responsable-club with valid licence
        $member = Member::factory()->create([
            'adh_license' => '123456',
            'adh_end_validity' => now()->addYear(),
        ]);
        
        $responsableClub = User::factory()->create(['adh_id' => $member->adh_id]);
        $responsableClub->assignRole('responsable-club');

        // Create club owned by different user
        $club = Club::factory()->create();

        $this->info("User ID: {$responsableClub->id}");
        $this->info("Club created_by: {$club->created_by}");
        $this->info("User has role responsable-club: " . ($responsableClub->hasRole('responsable-club') ? 'YES' : 'NO'));
        $this->info("User has permission edit-own-club: " . ($responsableClub->hasPermissionTo('edit-own-club') ? 'YES' : 'NO'));
        $this->info("Club hasManager(user): " . ($club->hasManager($responsableClub) ? 'YES' : 'NO'));
        $this->info("User can('update', club): " . ($responsableClub->can('update', $club) ? 'YES' : 'NO'));
        $this->info("Gate allows: " . (Gate::forUser($responsableClub)->allows('update', $club) ? 'YES' : 'NO'));

        return 0;
    }
}
