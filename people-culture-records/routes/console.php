<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('disciplinary:auto-close-expired')->daily();
Schedule::command('promotions:apply-effective')->daily();
Schedule::command('relocations:apply-effective')->daily();
