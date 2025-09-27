<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule blog scraping every 3 minutes on weekdays (Mon-Fri)
Schedule::command('scrape:blogs')
    ->everyThreeMinutes()
    ->weekdays()
    ->between('09:00', '17:00'); // Only during business hours

// Schedule blog scraping every 3 hours on weekends
Schedule::command('scrape:blogs')
    ->everyThreeHours()
    ->weekends();

// Schedule stock price fetching every 30 seconds
Schedule::command('stocks:fetch')
    ->everyThirtySeconds()
    ->weekdays();
