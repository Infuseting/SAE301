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
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting npm run dev and php artisan serve...');

        $npm = new Process(['npm', 'run', 'dev']);
        $npm->setWorkingDirectory(base_path());
        $npm->setTimeout(null);
        $npm->start();

        $artisan = new Process(['php', 'artisan', 'serve']);
        $artisan->setWorkingDirectory(base_path());
        $artisan->setTimeout(null);
        $artisan->start();

        $urlDisplayed = false;

        // Output both processes and display app URL when ready
        while ($npm->isRunning() || $artisan->isRunning()) {
            if ($npm->isRunning() && $npm->getIncrementalOutput()) {
                $this->output->write($npm->getIncrementalOutput());
            }
            if ($artisan->isRunning()) {
                $output = $artisan->getIncrementalOutput();
                if ($output) {
                    $this->output->write($output);
                    if (!$urlDisplayed && preg_match('/http:\/\/\S+:\d+/', $output, $matches)) {
                        $this->info("\nApp is accessible at: {$matches[0]}\n");
                        $urlDisplayed = true;
                    }
                }
            }
            usleep(100000); // 0.1s
        }

        $this->info('Both processes have stopped.');
        return 0;
    }
}
