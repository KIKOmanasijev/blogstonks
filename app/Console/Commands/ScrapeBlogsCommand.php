<?php

namespace App\Console\Commands;

use App\Services\ScrapingService;
use Illuminate\Console\Command;

class ScrapeBlogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:blogs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape blog posts from all active sites';

    /**
     * Execute the console command.
     */
    public function handle(ScrapingService $scrapingService)
    {
        $this->info('Starting blog scraping...');
        
        $scrapingService->scrapeAllCompanies();
        
        $this->info('Blog scraping completed.');
    }
}
