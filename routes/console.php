<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/
// routes/console.php

Schedule::command('wcp:generate-invoices')->monthlyOn(1, '06:00');
Schedule::command('wcp:apply-penalties')->dailyAt('00:01');
Schedule::command('wcp:daily-summary')->dailyAt('20:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
