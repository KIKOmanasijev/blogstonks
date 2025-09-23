<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class ClassifyAllPostsCommand extends Command
{
    protected $signature = 'posts:classify-all {--limit=10 : Maximum number of posts to classify} {--force : Force re-classification of already classified posts}';
    protected $description = 'Classify all unclassified posts (or all posts with --force)';

    public function handle()
    {
        if (!config('services.anthropic.api_key')) {
            $this->error('ANTHROPIC_API_KEY must be configured in your .env file');
            $this->info('Get your API key at: https://console.anthropic.com/');
            return 1;
        }

        $limit = (int) $this->option('limit');
        $force = $this->option('force');
        
        if ($force) {
            $posts = Post::with('company')
                ->limit($limit)
                ->get();
            $this->info("Force mode enabled. Found {$posts->count()} posts to re-classify. Starting classification...");
        } else {
            $posts = Post::whereNull('is_huge_news')
                ->with('company')
                ->limit($limit)
                ->get();
            $this->info("Found {$posts->count()} unclassified posts. Starting classification...");
        }

        if ($posts->isEmpty()) {
            $this->info($force ? 'No posts found.' : 'No unclassified posts found.');
            return 0;
        }
        
        $progressBar = $this->output->createProgressBar($posts->count());
        $progressBar->start();

        $classified = 0;
        $failed = 0;

        foreach ($posts as $post) {
            try {
                if ($post->classify($force)) {
                    $classified++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to classify post '{$post->title}': " . $e->getMessage());
            }
            
            $progressBar->advance();
            
            // Add a small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }

        $progressBar->finish();
        $this->newLine(2);
        
        $action = $force ? 're-classified' : 'classified';
        $this->info("Classification completed!");
        $this->info("✅ Successfully {$action}: {$classified}");
        $this->info("❌ Failed: {$failed}");

        return 0;
    }
}
