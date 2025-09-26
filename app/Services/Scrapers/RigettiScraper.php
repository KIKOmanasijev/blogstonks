<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Post;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RigettiScraper
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
                Log::error("Failed to fetch Rigetti newsroom: HTTP {$response->status()}");
                return;
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Find all blog post containers using the provided selector
            $postNodes = $xpath->query('//article[contains(@class, "grid-item")]');
            
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
                    Log::error("Error processing Rigetti post: " . $e->getMessage());
                    continue;
                }
            }

            // Update last scraped timestamp
            $company->update(['last_scraped_at' => now()]);
            
            // Don't mark posts as notified here - let classification handle notifications
            
            Log::info("Rigetti scraping completed. Scraped {$scrapedCount} posts, {$newPostsCount} new.");
            
        } catch (\Exception $e) {
            Log::error("Rigetti scraping failed: " . $e->getMessage());
        }
    }

    private function extractPostData(\DOMElement $postNode, \DOMXPath $xpath, Company $company): ?array
    {
        // Extract title
        $titleNodes = $xpath->query('.//h3', $postNode);
        if ($titleNodes->length === 0) {
            return null;
        }
        $title = trim($titleNodes->item(0)->textContent);

        // Extract date
        $dateNodes = $xpath->query('.//span[contains(@class, "date")]', $postNode);
        $publishedAt = null;
        if ($dateNodes->length > 0) {
            $dateString = trim($dateNodes->item(0)->textContent);
            $publishedAt = $this->parseDate($dateString);
        }

        // Extract content (description)
        $contentNodes = $xpath->query('.//p', $postNode);
        $content = '';
        if ($contentNodes->length > 0) {
            $content = trim($contentNodes->item(0)->textContent);
        }

        // Extract external link
        $linkNodes = $xpath->query('.//a[@class="read-more"]', $postNode);
        $externalUrl = null;
        if ($linkNodes->length > 0) {
            $externalUrl = $linkNodes->item(0)->getAttribute('href');
        }

        // Generate a unique URL for the post (since Rigetti doesn't have individual post pages)
        $postUrl = $externalUrl ?: $company->blog_url . '#' . $this->generateSlug($title);

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
            // Rigetti uses format like "18.09.25" (DD.MM.YY)
            $date = Carbon::createFromFormat('d.m.y', $dateString);
            return $date;
        } catch (\Exception $e) {
            try {
                // Fallback to other common formats
                return Carbon::parse($dateString);
            } catch (\Exception $e2) {
                Log::warning("Could not parse Rigetti date: {$dateString}");
                return null;
            }
        }
    }

    private function generateSlug(string $title): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
    }

    private function generateExternalId(string $title, string $date): string
    {
        return 'rigetti_' . md5($title . $date);
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
