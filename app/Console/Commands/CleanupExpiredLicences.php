<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LicenceService;

/**
 * Command to cleanup expired licences and update user roles
 */
class CleanupExpiredLicences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licences:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired licences and update user adherent roles';

    protected LicenceService $licenceService;

    public function __construct(LicenceService $licenceService)
    {
        parent::__construct();
        $this->licenceService = $licenceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up expired licences...');

        $affectedUsers = $this->licenceService->cleanupExpiredLicences();

        $this->info("Processed {$affectedUsers} users with expired licences.");

        return Command::SUCCESS;
    }
}
