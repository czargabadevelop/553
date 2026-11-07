<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Resolve rounds whose arrangement timer expired, so one absent player can
// never leave the other three waiting indefinitely.
Schedule::command('games:resolve-expired')->everyMinute()->withoutOverlapping();
