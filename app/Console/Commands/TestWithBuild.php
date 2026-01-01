<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TestWithBuild extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs npm run build then runs Laravel tests';

    public function __construct()
    {
        parent::__construct();
        // required to accept --filter, --stop-on-failure, etc.
        $this->ignoreValidationErrors();
    }

    /**
     * Execute the console command.
     * * @return int
     */
    public function handle(): int
    {
        // 1. Build execution
        $args = $_SERVER['argv'];
        $noBuild = false;
        $phpUnitOptions = [];

        foreach ($args as $key => $arg) {
            // ignore 'artisan' and 'test:build'
            if ($key < 2) continue;

            /*
            if ($arg === '--no-build') {
                $noBuild = true;
                continue;
            }
            */

            $phpUnitOptions[] = $arg;
        }


        // 2. Build execution
        if (!$noBuild) {
            $this->info('Building frontend (npm run build)...');

            $process = new Process(['npm', 'run', 'build']);
            $process->setTimeout(null);

            // Keep color and format in terminal
            $process->setTty(Process::isTtySupported());
            
            // Show real-time output
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $this->error('Build has failed. Tests cancelled.');
                return 1;
            }
            $this->info('Build successful.');
        }

        // 3. Prepare and run tests
        $this->info('Running tests...');

        // Retrieve additional arguments (e.g., --filter=UserTest)
        // Call the native Laravel 'test' command
        return $this->call('test', [
            'options' => $phpUnitOptions
        ]);
    }
}