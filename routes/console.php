<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\RunDevServer;

/** 
 * php artisan inspire
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/**
 * php artisan run
 */
Artisan::command('run', function () {
    $this->call(RunDevServer::class);
})->purpose('Run npm run dev and php artisan serve together');