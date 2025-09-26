<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Post;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QciScraper
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
                Log::error("Failed to fetch QCI news: HTTP {$response->status()}");
                return;
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Find all news card containers
            $postNodes = $xpath->query('//div[contains(@class, "MuiPaper-root") and contains(@class, "MuiCard-root")]');
            
            $scrapedCount = 0;
            $newPostsCount = 0;
            $maxPosts = 3;

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
                    Log::error("Error processing QCI post: " . $e->getMessage());
                    continue;
                }
            }

            $company->update(['last_scraped_at' => now()]);
            
            // Don't mark posts as notified here - let classification handle notifications
            
            Log::info("QCI scraping completed. Scraped {$scrapedCount} posts, {$newPostsCount} new.");
            
        } catch (\Exception $e) {
            Log::error("QCI scraping failed: " . $e->getMessage());
        }
    }

    private function extractPostData(\DOMElement $postNode, \DOMXPath $xpath, Company $company): ?array
    {
        // Extract title from h3 element
        $titleNodes = $xpath->query('.//h3[contains(@class, "MuiTypography-h4")]', $postNode);
        if ($titleNodes->length === 0) {
            return null;
        }
        $title = trim($titleNodes->item(0)->textContent);

        // Extract date from time element
        $dateNodes = $xpath->query('.//time[contains(@class, "MuiTypography-h5")]', $postNode);
        $publishedAt = null;
        if ($dateNodes->length > 0) {
            try {
                $dateString = trim($dateNodes->item(0)->textContent);
                $publishedAt = Carbon::parse($dateString);
            } catch (\Exception $e) {
                Log::warning("Could not parse date for QCI post: " . $e->getMessage());
            }
        }

        // Extract link from the "View" button
        $linkNodes = $xpath->query('.//a[contains(@class, "MuiButton-root")]', $postNode);
        $fullUrl = null;
        if ($linkNodes->length > 0) {
            $relativeUrl = $linkNodes->item(0)->getAttribute('href');
            $fullUrl = $this->buildFullUrl($relativeUrl, $company->url);
        }

        // Fetch full content from the individual post page
        $content = '';
        if ($fullUrl) {
            $content = $this->fetchPostContent($fullUrl);
        }

        return [
            'title' => $title,
            'content' => $content,
            'url' => $fullUrl ?: $company->blog_url,
            'published_at' => $publishedAt,
            'external_id' => $this->generateExternalId($fullUrl ?: $title),
        ];
    }

    private function fetchPostContent(string $url): string
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
                ->get($url);

            if (!$response->successful()) {
                Log::error("Failed to fetch QCI post content from {$url}: HTTP {$response->status()}");
                return '';
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Try multiple selectors for content
            $contentSelectors = [
                '//div[contains(@class, "MuiPaper-root") and contains(@class, "paperTemplate_paper__tvabE")]',
                '//div[contains(@class, "MuiPaper-root")]',
                '//article',
                '//div[contains(@class, "content")]',
                '//main',
                '//div[contains(@class, "post-content")]',
            ];

            foreach ($contentSelectors as $selector) {
                $contentNodes = $xpath->query($selector);
                if ($contentNodes->length > 0) {
                    $articleNode = $contentNodes->item(0);
                    
                    // Get all content except the h1 title
                    $contentElements = $xpath->query('.//*[not(self::h1)]', $articleNode);
                    $content = '';
                    
                    foreach ($contentElements as $element) {
                        if ($element->nodeType === XML_ELEMENT_NODE) {
                            $content .= $dom->saveHTML($element);
                        }
                    }
                    
                    if (strlen(trim(strip_tags($content))) > 50) {
                        return $content;
                    }
                }
            }

            // If no specific content found, return a placeholder
            return '<p>Content available at: <a href="' . $url . '">' . $url . '</a></p>';
        } catch (\Exception $e) {
            Log::error("Failed to fetch QCI post content from {$url}: " . $e->getMessage());
            return '<p>Content available at: <a href="' . $url . '">' . $url . '</a></p>';
        }
    }

    private function buildFullUrl(string $relativeUrl, string $baseUrl): string
    {
        if (filter_var($relativeUrl, FILTER_VALIDATE_URL)) {
            return $relativeUrl;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($relativeUrl, '/');
    }

    private function generateExternalId(string $identifier): string
    {
        return md5($identifier);
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
