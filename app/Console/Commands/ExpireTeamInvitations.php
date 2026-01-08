<?php

namespace App\Console\Commands;

use App\Models\TemporaryTeamInvitation;
use Illuminate\Console\Command;

/**
 * Command to expire old team invitations and clean up temporary team data.
 */
class ExpireTeamInvitations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'invitations:expire';

    /**
     * The console command description.
     */
    protected $description = 'Expire old team invitations and remove expired members from temporary teams';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired invitations...');

        // Find all expired invitations that are still pending
        $expiredInvitations = TemporaryTeamInvitation::where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expiredInvitations as $invitation) {
            // Mark as expired
            $invitation->expire();

            // Remove from temporary_team_data
            $registration = $invitation->registration;
            if ($registration && $registration->temporary_team_data) {
                $teamData = $registration->temporary_team_data;

                // Filter out the expired member
                $teamData = array_filter($teamData, function ($member) use ($invitation) {
                    return $member['email'] !== $invitation->email;
                });

                // Reindex array
                $teamData = array_values($teamData);

                $registration->update(['temporary_team_data' => $teamData]);

                $this->info("Removed {$invitation->email} from registration #{$registration->reg_id}");
            }

            $count++;
        }

        $this->info("Expired {$count} invitation(s)");

        return Command::SUCCESS;
    }
}
