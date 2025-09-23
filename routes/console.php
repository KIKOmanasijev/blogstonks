<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule blog scraping every 5 minutes
Schedule::command('scrape:blogs')->everyFiveMinutes();

// Schedule stock price fetching every minute
Schedule::command('stocks:fetch')->everyMinute();
