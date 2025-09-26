<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Post;
use App\Services\ScrapingService;
use App\Services\TelegramNotificationService;
use App\Services\ProxyService;
use Illuminate\Console\Command;

class RefetchCompanyPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:refetch {ticker : The company ticker to refetch posts for} {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all posts for a company and refetch them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ticker = $this->argument('ticker');
        
        // Find the company
        $company = Company::where('ticker', $ticker)->first();
        
        if (!$company) {
            $this->error("Company with ticker '{$ticker}' not found.");
            return 1;
        }
        
        $this->info("Found company: {$company->name} ({$company->ticker})");
        
        // Count existing posts
        $existingPostsCount = $company->posts()->count();
        $this->info("Found {$existingPostsCount} existing posts for {$company->name}");
        
        if ($existingPostsCount > 0) {
            // Ask for confirmation unless --force is used
            if (!$this->option('force') && !$this->confirm("Are you sure you want to delete all {$existingPostsCount} posts for {$company->name}?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            // Delete all posts for this company
            $deletedCount = $company->posts()->delete();
            $this->info("Deleted {$deletedCount} posts for {$company->name}");
        }
        
        // Refetch posts
        $this->info("Refetching posts for {$company->name}...");
        
        $scrapingService = new ScrapingService(
            new TelegramNotificationService(),
            new ProxyService()
        );
        
        $scrapingService->scrapeCompany($company);
        
        // Count new posts
        $newPostsCount = $company->posts()->count();
        $this->info("Successfully refetched {$newPostsCount} posts for {$company->name}");
        
        // Show recent posts
        $recentPosts = $company->posts()
            ->orderBy('published_at', 'desc')
            ->take(5)
            ->get();
            
        if ($recentPosts->count() > 0) {
            $this->info("\nRecent posts:");
            foreach ($recentPosts as $post) {
                $this->line("- {$post->title} ({$post->published_at->format('Y-m-d H:i')})");
            }
        }
        
        return 0;
    }
}