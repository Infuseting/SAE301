<?php

namespace App\Console\Commands;

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
        // Create storage link first
        $this->info('Creating storage link...');
        $this->call('storage:link');
        $this->info('Storage link created successfully!');

        $this->info('Starting development servers in 2 seconds...');

        sleep(2);
        $this->newLine();

        // Initialize processes (or use mocks if provided)
        $npm = $this->processMocks['npm'] ?? new Process(['npm', 'run', 'dev']);
        $artisan = $this->processMocks['artisan'] ?? new Process(['php', 'artisan', 'serve']);

        $npm->setWorkingDirectory(base_path());
        $npm->setTimeout(null);
        $npm->setTty(Process::isTtySupported());
        $npm->start();

        $artisan->setWorkingDirectory(base_path());
        $artisan->setTimeout(null);
        $artisan->setTty(Process::isTtySupported());
        $artisan->start();

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
                }
            }
            
            usleep(100000); // Wait 0.1s to prevent CPU spiking
        }

        $this->info('Both processes have stopped.');

        return 0;
    }
}