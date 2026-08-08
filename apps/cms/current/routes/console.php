<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('app:version', function (): void {
    $this->info((string) config('app.version'));
})->purpose('Prikaži verziju Ald1n CMS aplikacije');

Schedule::command('exchange-rate:update')
    ->dailyAt('06:15')
    ->withoutOverlapping(30);

Schedule::command('app:automation-run')
    ->hourlyAt(10)
    ->withoutOverlapping(55);

Schedule::command('app:automation-run --digest')
    ->dailyAt('08:05')
    ->withoutOverlapping(60);



Schedule::command('app:order-email-dispatch')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('app:mobile-push-dispatch --limit=100')
    ->everyMinute()
    ->withoutOverlapping(5);


Schedule::command('app:management-report-dispatch --limit=100')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

Schedule::command('app:scheduler-heartbeat')
    ->everyMinute()
    ->withoutOverlapping(2);

Schedule::command('app:customer-portal-maintenance')
    ->dailyAt('03:45')
    ->withoutOverlapping(15);

Schedule::command('app:backup-create --type=daily')
    ->dailyAt('02:30')
    ->withoutOverlapping(120);

Schedule::command('app:backup-create --type=weekly')
    ->weeklyOn(0, '03:10')
    ->withoutOverlapping(180);

Schedule::command('app:system-health --snapshot')
    ->dailyAt('07:45')
    ->withoutOverlapping(30);
