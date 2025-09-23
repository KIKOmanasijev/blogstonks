<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Post;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DwaveScraper
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
                Log::error("Failed to fetch D-Wave newsroom: HTTP {$response->status()}");
                return;
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Find all news item containers
            $postNodes = $xpath->query('//div[contains(@class, "cp-news-item-teaser")]');
            
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
                            // Classify the new post
                            $this->classifyPost($postData, $company);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing D-Wave post: " . $e->getMessage());
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
            
            Log::info("D-Wave scraping completed. Scraped {$scrapedCount} posts, {$newPostsCount} new.");
            
        } catch (\Exception $e) {
            Log::error("D-Wave scraping failed: " . $e->getMessage());
        }
    }

    private function extractPostData(\DOMElement $postNode, \DOMXPath $xpath, Company $company): ?array
    {
        try {
            // Extract title and link
            $titleNode = $xpath->query('.//h3/a', $postNode)->item(0);
            if (!$titleNode) {
                Log::warning("No title found for D-Wave post");
                return null;
            }

            $title = trim($titleNode->textContent);
            $relativeUrl = $titleNode->getAttribute('href');
            
            if (!$title || !$relativeUrl) {
                Log::warning("Missing title or URL for D-Wave post");
                return null;
            }

            // Extract date
            $dateNode = $xpath->query('.//span[contains(@class, "eyebrow__label")]', $postNode)->item(0);
            $publishedAt = null;
            
            if ($dateNode) {
                $dateText = trim($dateNode->textContent);
                try {
                    $publishedAt = Carbon::parse($dateText);
                } catch (\Exception $e) {
                    Log::warning("Could not parse D-Wave date: {$dateText}");
                    $publishedAt = now();
                }
            } else {
                $publishedAt = now();
            }

            // Build full URL
            $fullUrl = $this->buildFullUrl($relativeUrl, $company->url);
            
            // Fetch content from individual post page
            $content = $this->fetchPostContent($fullUrl);

            // Create external ID from URL
            $externalId = $this->createExternalId($relativeUrl);

            return [
                'title' => $title,
                'content' => $content,
                'url' => $fullUrl,
                'published_at' => $publishedAt,
                'external_id' => $externalId,
            ];

        } catch (\Exception $e) {
            Log::error("Error extracting D-Wave post data: " . $e->getMessage());
            return null;
        }
    }

    private function fetchPostContent(string $url): string
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                Log::warning("Failed to fetch D-Wave post content from: {$url}");
                return '';
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Extract content from .article-body
            $contentNodes = $xpath->query('//div[contains(@class, "article-body")]');
            
            if ($contentNodes->length > 0) {
                $content = '';
                foreach ($contentNodes as $node) {
                    $content .= $this->getNodeText($node) . "\n\n";
                }
                return trim($content);
            }

            // Fallback: try to get any content from the page
            $bodyNodes = $xpath->query('//body');
            if ($bodyNodes->length > 0) {
                return $this->getNodeText($bodyNodes->item(0));
            }

            return '';

        } catch (\Exception $e) {
            Log::error("Error fetching D-Wave post content from {$url}: " . $e->getMessage());
            return '';
        }
    }

    private function getNodeText(\DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $text .= $this->getNodeText($child) . "\n";
            }
        }
        return trim($text);
    }

    private function buildFullUrl(string $relativeUrl, string $baseUrl): string
    {
        if (str_starts_with($relativeUrl, 'http')) {
            return $relativeUrl;
        }

        if (str_starts_with($relativeUrl, '/')) {
            $parsedBase = parse_url($baseUrl);
            return $parsedBase['scheme'] . '://' . $parsedBase['host'] . $relativeUrl;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($relativeUrl, '/');
    }

    private function createExternalId(string $url): string
    {
        // Create a unique ID from the URL path
        $path = parse_url($url, PHP_URL_PATH);
        return 'dwave_' . md5($path);
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
