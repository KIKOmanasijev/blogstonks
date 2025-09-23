<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\AnthropicService;
use Illuminate\Console\Command;

class TestPostClassificationCommand extends Command
{
    protected $signature = 'test:post-classification {--post-id= : ID of specific post to classify}';
    protected $description = 'Test post classification functionality';

    public function handle()
    {
        if (!config('services.anthropic.api_key')) {
            $this->error('ANTHROPIC_API_KEY must be configured in your .env file');
            $this->info('Get your API key at: https://console.anthropic.com/');
            return 1;
        }

        $postId = $this->option('post-id');
        
        if ($postId) {
            $post = Post::find($postId);
            if (!$post) {
                $this->error("Post with ID {$postId} not found.");
                return 1;
            }
            $this->classifyPost($post);
        } else {
            // Find an unclassified post
            $post = Post::whereNull('is_huge_news')->first();
            if (!$post) {
                $this->info('No unclassified posts found.');
                return 0;
            }
            $this->classifyPost($post);
        }

        return 0;
    }

    private function classifyPost(Post $post): void
    {
        $this->info("Classifying post: {$post->title}");
        $this->info("Company: {$post->company->name}");
        $this->info("URL: {$post->url}");
        
        if ($post->isClassified()) {
            $this->warn("Post is already classified:");
            $this->info("  Huge News: " . ($post->is_huge_news ? 'Yes' : 'No'));
            $this->info("  Score: {$post->importance_score}/100");
            $this->info("  Scored At: {$post->scored_at}");
            return;
        }

        $this->info("Calling Anthropic API...");
        
        $success = $post->classify();
        
        if ($success) {
            $post->refresh();
            $this->info("✅ Post classified successfully!");
            $this->info("  Huge News: " . ($post->is_huge_news ? 'Yes' : 'No'));
            $this->info("  Score: {$post->importance_score}/100");
            $this->info("  Scored At: {$post->scored_at}");
        } else {
            $this->error("❌ Failed to classify post. Check logs for details.");
        }
    }
}
