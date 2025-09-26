<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    protected ?string $botToken;
    protected ?string $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
    }

    public function sendNewPostNotification(Post $post): void
    {
        if (!$this->isConfigured()) {
            Log::warning('Telegram bot not configured, skipping notification');
            return;
        }

        try {
            $message = $this->buildSinglePostMessage($post);
            
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send Telegram notification', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'post_id' => $post->id,
                ]);
            } else {
                Log::info('Telegram notification sent successfully', [
                    'post_id' => $post->id,
                    'site' => $post->site->name,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending Telegram notification', [
                'error' => $e->getMessage(),
                'post_id' => $post->id,
            ]);
        }
    }

    public function sendMultiplePostsNotification(Company $company, int $count): void
    {
        if (!$this->isConfigured()) {
            Log::warning('Telegram bot not configured, skipping notification');
            return;
        }

        try {
            $message = $this->buildMultiplePostsMessage($company, $count);
            
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send Telegram notification for multiple posts', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'company_id' => $company->id,
                    'count' => $count,
                ]);
            } else {
                Log::info('Telegram notification sent successfully for multiple posts', [
                    'company_id' => $company->id,
                    'company' => $company->name,
                    'count' => $count,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending Telegram notification for multiple posts', [
                'error' => $e->getMessage(),
                'company_id' => $company->id,
            ]);
        }
    }

    public function sendHighScorePostNotification(Post $post): void
    {
        if (!$this->isConfigured()) {
            Log::warning('Telegram bot not configured, skipping high-score notification');
            return;
        }

        try {
            $message = $this->buildHighScorePostMessage($post);
            
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send Telegram high-score notification', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'post_id' => $post->id,
                ]);
            } else {
                Log::info('Telegram high-score notification sent successfully', [
                    'post_id' => $post->id,
                    'company' => $post->company->name,
                    'score' => $post->importance_score,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending Telegram high-score notification', [
                'error' => $e->getMessage(),
                'post_id' => $post->id,
            ]);
        }
    }

    private function buildSinglePostMessage(Post $post): string
    {
        $company = $post->company;
        
        if (!$company) {
            Log::error('Post has no associated company', ['post_id' => $post->id]);
            return "📰 <b>New Blog Post!</b>\n\n📝 <b>{$this->escapeHtml($post->title)}</b>\n\n";
        }
        
        $emoji = $this->getCompanyEmoji($company->name);
        
        $message = "{$emoji} <b>New Blog Post from {$company->name}!</b>\n\n";
        $message .= "📝 <b>{$this->escapeHtml($post->title)}</b>\n\n";
        
        if ($post->content && strlen($post->content) > 0) {
            $preview = $this->truncateContent($post->content, 200);
            $message .= "📄 <i>{$this->escapeHtml($preview)}</i>\n\n";
        }
        
        $message .= "🔗 <a href=\"{$post->url}\">Read full post</a>\n";
        $message .= "🏢 <a href=\"{$company->url}\">{$company->name} Website</a>\n";
        $message .= "📅 Published: {$post->published_at->format('M j, Y \a\t g:i A')}";
        
        return $message;
    }

    private function buildMultiplePostsMessage(Company $company, int $count): string
    {
        $emoji = $this->getCompanyEmoji($company->name);
        
        $message = "{$emoji} <b>{$count} New Blog Posts from {$company->name}!</b>\n\n";
        $message .= "We found <b>{$count} new blog posts</b> from {$company->name}.\n\n";
        $message .= "🔗 <a href=\"{$company->blog_url}\">View all posts on blog</a>\n";
        $message .= "🏢 <a href=\"{$company->url}\">{$company->name} Website</a>\n";
        $message .= "📊 Total posts monitored: {$company->posts()->count()}";
        
        return $message;
    }

    private function buildHighScorePostMessage(Post $post): string
    {
        $company = $post->company;
        $emoji = $this->getCompanyEmoji($company->name);
        
        // Determine the alert level based on score
        $alertLevel = $post->importance_score >= 90 ? '🚨 CRITICAL' : 
                     ($post->importance_score >= 80 ? '🔥 HIGH' : '⭐ IMPORTANT');
        
        $message = "{$alertLevel} NEWS ALERT! {$emoji}\n\n";
        $message .= "📊 <b>Importance Score: {$post->importance_score}/100</b>\n";
        
        if ($post->is_huge_news) {
            $message .= "🚨 <b>HUGE NEWS DETECTED!</b>\n\n";
        }
        
        $message .= "🏢 <b>{$company->name}</b>\n";
        $message .= "📝 <b>{$this->escapeHtml($post->title)}</b>\n\n";
        
        if ($post->content && strlen($post->content) > 0) {
            $preview = $this->truncateContent($post->content, 300);
            $message .= "📄 <i>{$this->escapeHtml($preview)}</i>\n\n";
        }
        
        $message .= "🔗 <a href=\"{$post->url}\">Read full post</a>\n";
        $message .= "🏢 <a href=\"{$company->url}\">{$company->name} Website</a>\n";
        $message .= "📅 Published: {$post->published_at->format('M j, Y \a\t g:i A')}\n";
        $message .= "🤖 Scored: {$post->scored_at->format('M j, Y \a\t g:i A')}";
        
        return $message;
    }

    private function getCompanyEmoji(string $companyName): string
    {
        $emojis = [
            'ionq' => '⚛️',
            'rigetti' => '🔬',
            'd-wave' => '🌊',
            'intel' => '🔵',
            'sealsq' => '🔐',
            'amd' => '🔴',
            'ibm' => '🔵',
            'google' => '🔍',
            'microsoft' => '🪟',
            'amazon' => '📦',
        ];

        $name = strtolower($companyName);
        
        foreach ($emojis as $key => $emoji) {
            if (str_contains($name, $key)) {
                return $emoji;
            }
        }

        return '📝'; // Default emoji
    }

    private function truncateContent(string $content, int $length): string
    {
        $cleanContent = strip_tags($content);
        $cleanContent = preg_replace('/\s+/', ' ', $cleanContent);
        
        if (strlen($cleanContent) <= $length) {
            return $cleanContent;
        }
        
        return substr($cleanContent, 0, $length) . '...';
    }

    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    private function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->chatId);
    }
}
