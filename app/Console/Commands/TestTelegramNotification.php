<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Company;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class TestTelegramNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:telegram-notification {--type=single : Type of notification to test (single|multiple)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telegram notification functionality';

    /**
     * Execute the console command.
     */
    public function handle(TelegramNotificationService $telegramService)
    {
        $type = $this->option('type');

        if (!config('services.telegram.bot_token') || !config('services.telegram.chat_id')) {
            $this->error('TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID must be configured in your .env file');
            return 1;
        }

        $company = Company::first();
        if (!$company) {
            $this->error('No companies found in database. Please run the seeder first.');
            return 1;
        }

        if ($type === 'multiple') {
            $this->info('Testing multiple posts notification...');
            $telegramService->sendMultiplePostsNotification($company, 3);
            $this->info('Multiple posts notification sent!');
        } else {
            $this->info('Testing single post notification...');
            $post = $company->posts()->first();
            
            if (!$post) {
                $this->error('No posts found for the company. Please run the scraper first.');
                return 1;
            }

            $telegramService->sendNewPostNotification($post);
            $this->info('Single post notification sent!');
        }

        return 0;
    }
}
