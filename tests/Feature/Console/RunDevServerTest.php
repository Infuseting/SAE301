<?php

namespace Tests\Feature\Console;

use Tests\TestCase;
use App\Console\Commands\RunDevServer;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;
use Mockery;

class RunDevServerTest extends TestCase
{
    /**
     * Clean up Mockery after each test.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test if the 'run' command class is correctly registered.
     */
    public function testRunCommandIsRegistered(): void
    {
        $this->assertTrue(class_exists(RunDevServer::class));
    }

    /**
     * Test the command execution using Mocks to prevent infinite loops.
     */
    public function testRunCommandStartsAndExitsSafelyInTest(): void
    {
        // 1. Create mocks for the two processes (npm and artisan)
        $npmMock = Mockery::mock(Process::class);
        $artisanMock = Mockery::mock(Process::class);

        // Define expected behavior for both mocks
        foreach ([$npmMock, $artisanMock] as $mock) {
            $mock->shouldReceive('setWorkingDirectory')->andReturnSelf();
            $mock->shouldReceive('setTimeout')->andReturnSelf();
            $mock->shouldReceive('setTty')->andReturnSelf();
            $mock->shouldReceive('start')->once();
            $mock->shouldReceive('isRunning')->andReturn(false); // Stop the loop immediately
        }

        // 2. We need to tell Laravel to use our mocks when running the command.
        // We do this by resolving the command instance and setting the property.
        $this->app->extend(RunDevServer::class, function ($service) use ($npmMock, $artisanMock) {
            $service->processMocks = [
                'npm' => $npmMock,
                'artisan' => $artisanMock,
            ];
            return $service;
        });

        // 3. Execute the command
        $this->artisan('run')
            ->expectsOutput('Starting npm run dev and php artisan serve...')
            ->expectsOutput('Both processes have stopped.')
            ->assertExitCode(0);
    }

    /**
     * Test the purpose description by checking the artisan list.
     */
    public function testRunCommandHasCorrectDescription(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('Run npm run dev and php artisan serve together')
            ->assertExitCode(0);
    }
}