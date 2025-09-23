<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class MarkExistingPostsAsNotified extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:mark-notified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark all existing posts as notified (to prevent duplicate notifications)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Marking existing posts as notified...');
        
        $count = Post::whereNull('user_notified_at')
            ->update(['user_notified_at' => now()]);
        
        $this->info("Marked {$count} posts as notified.");
        
        return 0;
    }
}
