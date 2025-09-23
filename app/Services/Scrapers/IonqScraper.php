<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Post;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IonqScraper
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
                Log::error("Failed to fetch IonQ blog: HTTP {$response->status()}");
                return;
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Find all blog post containers
            $postNodes = $xpath->query('//div[contains(@class, "ResourceGridItem")]');
            
            $scrapedCount = 0;
            $newPostsCount = 0;
            $maxPosts = 3; // Only scrape last 3 posts as requested

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
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing IonQ post: " . $e->getMessage());
                    continue;
                }
            }

            // Update last scraped timestamp
            $company->update(['last_scraped_at' => now()]);
            
            // Send Telegram notification if there are new posts
            if ($newPostsCount > 0) {
                // Get posts that haven't been notified yet
                $unnotifiedPosts = $company->posts()
                    ->whereNull('user_notified_at')
                    ->orderBy('published_at', 'desc')
                    ->get();

                if ($unnotifiedPosts->count() === 1) {
                    // Send individual notification for single new post
                    $post = $unnotifiedPosts->first();
                    $post->load('company'); // Ensure company relationship is loaded
                    $this->telegramService->sendNewPostNotification($post);
                    // Mark as notified
                    $post->update(['user_notified_at' => now()]);
                } else {
                    // Send summary notification for multiple new posts
                    $this->telegramService->sendMultiplePostsNotification($company, $unnotifiedPosts->count());
                    // Mark all as notified
                    $company->posts()
                        ->whereNull('user_notified_at')
                        ->update(['user_notified_at' => now()]);
                }
            }
            
            Log::info("IonQ scraping completed. Scraped {$scrapedCount} posts, {$newPostsCount} new.");
            
        } catch (\Exception $e) {
            Log::error("IonQ scraping failed: " . $e->getMessage());
        }
    }

    private function extractPostData(\DOMElement $postNode, \DOMXPath $xpath, Company $company): ?array
    {
        // Extract title
        $titleNodes = $xpath->query('.//span[contains(@class, "resources-item-title")]', $postNode);
        if ($titleNodes->length === 0) {
            return null;
        }
        $title = trim($titleNodes->item(0)->textContent);

        // Extract link
        $linkNodes = $xpath->query('.//a[@class="resources-panel"]', $postNode);
        if ($linkNodes->length === 0) {
            return null;
        }
        $relativeUrl = $linkNodes->item(0)->getAttribute('href');
        $fullUrl = $this->buildFullUrl($relativeUrl, $company->url);

        // Extract date
        $dateNodes = $xpath->query('.//span[contains(@class, "resources-item-date")]', $postNode);
        $publishedAt = null;
        if ($dateNodes->length > 0) {
            $dateString = trim($dateNodes->item(0)->textContent);
            $publishedAt = $this->parseDate($dateString);
        }

        // Extract content (we'll need to fetch the full post for content)
        $content = $this->fetchPostContent($fullUrl);

        return [
            'title' => $title,
            'url' => $fullUrl,
            'published_at' => $publishedAt,
            'content' => $content,
            'external_id' => $this->generateExternalId($fullUrl),
        ];
    }

    private function buildFullUrl(string $relativeUrl, string $baseUrl): string
    {
        if (str_starts_with($relativeUrl, 'http')) {
            return $relativeUrl;
        }
        
        if (str_starts_with($relativeUrl, '/')) {
            return rtrim($baseUrl, '/') . $relativeUrl;
        }
        
        return rtrim($baseUrl, '/') . '/' . ltrim($relativeUrl, '/');
    }

    private function parseDate(string $dateString): ?Carbon
    {
        try {
            // IonQ uses format like "June 13, 2025"
            return Carbon::createFromFormat('F j, Y', $dateString);
        } catch (\Exception $e) {
            try {
                // Fallback to other common formats
                return Carbon::parse($dateString);
            } catch (\Exception $e2) {
                Log::warning("Could not parse date: {$dateString}");
                return null;
            }
        }
    }

    private function fetchPostContent(string $url): string
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                return '';
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Try to find the main content area
            $contentSelectors = [
                '//article',
                '//div[contains(@class, "content")]',
                '//div[contains(@class, "post-content")]',
                '//div[contains(@class, "entry-content")]',
                '//main',
            ];

            foreach ($contentSelectors as $selector) {
                $contentNodes = $xpath->query($selector);
                if ($contentNodes->length > 0) {
                    $content = '';
                    foreach ($contentNodes as $node) {
                        $content .= $this->getTextContent($node);
                    }
                    if (!empty(trim($content))) {
                        return trim($content);
                    }
                }
            }

            // Fallback: get all text content
            return $this->getTextContent($dom->documentElement);
            
        } catch (\Exception $e) {
            Log::error("Failed to fetch post content from {$url}: " . $e->getMessage());
            return '';
        }
    }

    private function getTextContent(\DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $text .= $this->getTextContent($child) . ' ';
            }
        }
        return $text;
    }

    private function generateExternalId(string $url): string
    {
        return md5($url);
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
}
