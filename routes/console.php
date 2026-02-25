<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
*/

// 1. Expire offers - Daily at midnight
Schedule::command('offers:expire')
    ->daily()
    ->at('00:00')
    ->withoutOverlapping()
    ->runInBackground();

// 2. Expire stale subscriptions - Daily at 5:50 AM (before renewal job)
Schedule::command('subscriptions:expire')
    ->dailyAt('05:50')
    ->withoutOverlapping()
    ->runInBackground();

// 3. Process subscription renewals - Daily at 6:00 AM
Schedule::command('subscriptions:process-renewals')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground();

// 4. Send match reminders - Every 30 minutes
Schedule::command('matchday:send-reminders')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// 5. Cleanup expired OTPs - Daily at 3:00 AM
Schedule::command('matchday:cleanup-otps')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();

// 6. Update match statuses - Every 5 minutes
Schedule::command('matchday:update-match-statuses')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// 7. Update occupancy cache - Every 2 minutes
Schedule::command('matchday:update-occupancy-cache')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground();
