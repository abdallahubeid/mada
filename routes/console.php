<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tenant:send-expiring-contract-notifications')
    ->dailyAt('07:00')
    ->withoutOverlapping();

Schedule::command('tenant:send-subscription-renewal-notifications')
    ->dailyAt('07:15')
    ->withoutOverlapping();

Schedule::command('tenant:send-check-out-reminders')
    ->dailyAt('18:30')
    ->weekdays()
    ->withoutOverlapping();
