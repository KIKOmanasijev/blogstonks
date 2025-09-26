<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule blog scraping every 3 minutes
Schedule::command('scrape:blogs')->everyThreeMinutes();

// Schedule stock price fetching every 30 seconds
Schedule::command('stocks:fetch')->everyThirtySeconds();
