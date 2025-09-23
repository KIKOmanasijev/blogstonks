<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Post;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IntelScraper
{
    protected TelegramNotificationService $telegramService;

    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function scrape(Company $company): void
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'DNT' => '1',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                ])
                ->get($company->blog_url);
            
            if (!$response->successful()) {
                Log::error("Failed to fetch Intel newsroom: HTTP {$response->status()}");
                return;
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Find all blog post containers using the provided selector
            $postNodes = $xpath->query('//div[contains(@class, "post-result-item-container")]');
            
            $scrapedCount = 0;
            $newPostsCount = 0;
            $maxPosts = 5; // Scrape last 5 posts

            foreach ($postNodes as $index => $postNode) {
                if ($scrapedCount >= $maxPosts) {
                    break;
                }

                try {
                    $postData = $this->extractPostData($postNode, $xpath, $company);
                    
                    if ($postData) {
                        $isNewPost = $this->savePost($postData, $company);
                        $scrapedCount++;
                        
                        if ($isNewPost) {
                            $newPostsCount++;
                            // Classify the new post
                            $this->classifyPost($postData, $company);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing Intel post: " . $e->getMessage());
                    continue;
                }
            }

            // Update last scraped timestamp
            $company->update(['last_scraped_at' => now()]);
            
            // Mark all new posts as notified (notifications now handled by classification)
            if ($newPostsCount > 0) {
                $company->posts()
                    ->whereNull('user_notified_at')
                    ->update(['user_notified_at' => now()]);
            }
            
            Log::info("Intel scraping completed. Scraped {$scrapedCount} posts, {$newPostsCount} new.");
            
        } catch (\Exception $e) {
            Log::error("Intel scraping failed: " . $e->getMessage());
        }
    }

    private function extractPostData(\DOMElement $postNode, \DOMXPath $xpath, Company $company): ?array
    {
        // Extract title
        $titleNodes = $xpath->query('.//h2', $postNode);
        if ($titleNodes->length === 0) {
            return null;
        }
        $title = trim($titleNodes->item(0)->textContent);

        // Extract date
        $dateNodes = $xpath->query('.//p[contains(@class, "item-post-date")]', $postNode);
        $publishedAt = null;
        if ($dateNodes->length > 0) {
            $dateString = trim($dateNodes->item(0)->textContent);
            $publishedAt = $this->parseDate($dateString);
        }

        // Extract content (excerpt)
        $contentNodes = $xpath->query('.//p[contains(@class, "item-excerpt")]', $postNode);
        $content = '';
        if ($contentNodes->length > 0) {
            $content = trim($contentNodes->item(0)->textContent);
        }

        // Extract URL
        $linkNodes = $xpath->query('.//a[contains(@class, "post-result-item")]', $postNode);
        $postUrl = $company->blog_url; // fallback
        if ($linkNodes->length > 0) {
            $postUrl = $linkNodes->item(0)->getAttribute('href');
        }

        return [
            'title' => $title,
            'url' => $postUrl,
            'published_at' => $publishedAt,
            'content' => $content,
            'external_id' => $this->generateExternalId($title, $dateString ?? ''),
        ];
    }

    private function parseDate(string $dateString): ?Carbon
    {
        try {
            // Intel uses format like "September 18, 2025"
            return Carbon::createFromFormat('F j, Y', $dateString);
        } catch (\Exception $e) {
            try {
                // Fallback to other common formats
                return Carbon::parse($dateString);
            } catch (\Exception $e2) {
                Log::warning("Could not parse Intel date: {$dateString}");
                return null;
            }
        }
    }

    private function generateExternalId(string $title, string $date): string
    {
        return 'intel_' . md5($title . $date);
    }

    private function savePost(array $postData, Company $company): bool
    {
        $existingPost = Post::where('company_id', $company->id)
            ->where('external_id', $postData['external_id'])
            ->first();

        $isNewPost = !$existingPost;

        Post::updateOrCreate(
            [
                'company_id' => $company->id,
                'external_id' => $postData['external_id'],
            ],
            [
                'title' => $postData['title'],
                'content' => $postData['content'],
                'url' => $postData['url'],
                'published_at' => $postData['published_at'] ?? now(),
            ]
        );

        return $isNewPost;
    }

    private function classifyPost(array $postData, Company $company): void
    {
        try {
            $post = Post::where('company_id', $company->id)
                ->where('external_id', $postData['external_id'])
                ->first();

            if ($post && !$post->isClassified()) {
                $post->classify();
                Log::info("Post classified for {$company->name}: {$post->title}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to classify post for {$company->name}: " . $e->getMessage());
        }
    }
}
