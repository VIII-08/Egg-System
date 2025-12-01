<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the forecasting analysis to run daily at 2:00 AM
Schedule::command('forecast:run')
    ->dailyAt('02:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Log::error('Forecast command failed to run on schedule.');
    })
    ->onSuccess(function () {
        \Log::info('Forecast command completed successfully on schedule.');
    });
