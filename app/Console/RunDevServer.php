<?php

namespace App\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunDevServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run npm run dev and php artisan serve together';

    /**
     * Optional process overrides for testing purposes.
     * * @var array<string, Process>|null
     */
    public ?array $processMocks = null;

    /**
     * Execute the console command.
     * * @return int
     */
    public function handle(): int
    {
        $this->info('Starting npm run dev and php artisan serve...');

        // Initialize processes (or use mocks if provided)
        $npm = $this->processMocks['npm'] ?? new Process(['npm', 'run', 'dev']);
        $artisan = $this->processMocks['artisan'] ?? new Process(['php', 'artisan', 'serve']);

        $npm->setWorkingDirectory(base_path());
        $npm->setTimeout(null);
        $npm->start();

        $artisan->setWorkingDirectory(base_path());
        $artisan->setTimeout(null);
        $artisan->start();

        $urlDisplayed = false;

        // Loop while processes are running
        while ($npm->isRunning() || $artisan->isRunning()) {
            // Stop immediately if we are in a testing environment and no mocks are used
            if (app()->environment('testing') && $this->processMocks === null) {
                $npm->stop();
                $artisan->stop();
                break;
            }

            if ($npm->isRunning() && $npm->getIncrementalOutput()) {
                $this->output->write($npm->getIncrementalOutput());
            }

            if ($artisan->isRunning()) {
                $output = $artisan->getIncrementalOutput();
                if ($output) {
                    $this->output->write($output);
                    
                    // Display accessible URL once
                    if (!$urlDisplayed && preg_match('/http:\/\/\S+:\d+/', $output, $matches)) {
                        $this->info("\nApp is accessible at: {$matches[0]}\n");
                        $urlDisplayed = true;
                    }
                }
            }
            
            usleep(100000); // Wait 0.1s to prevent CPU spiking
        }

        $this->info('Both processes have stopped.');

        return 0;
    }
}