<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Post;
use App\Services\TelegramNotificationService;
use App\Services\ProxyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TeslaScraper
{
    protected TelegramNotificationService $telegramService;
    protected ProxyService $proxyService;

    public function __construct(TelegramNotificationService $telegramService, ProxyService $proxyService)
    {
        $this->telegramService = $telegramService;
        $this->proxyService = $proxyService;
    }

    public function scrape(Company $company): void
    {
        try {
            // Try proxy first, fallback to direct request
            $html = $this->proxyService->fetchWithProxy($company->blog_url);
            
            if (!$html) {
                Log::warning("Proxy failed, trying direct request for Tesla blog");
                $html = $this->proxyService->fetchDirect($company->blog_url);
            }
            
            if (!$html) {
                Log::error("Failed to fetch Tesla blog with both proxy and direct methods");
                return;
            }
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Find all blog post containers
            $postNodes = $xpath->query('//section[contains(@class, "tcl-article-teaser")]');
            
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
                    Log::error("Error processing Tesla post: " . $e->getMessage());
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
            
            Log::info("Tesla scraping completed. Scraped {$scrapedCount} posts, {$newPostsCount} new.");
            
        } catch (\Exception $e) {
            Log::error("Tesla scraping failed: " . $e->getMessage());
        }
    }

    private function extractPostData(\DOMElement $postNode, \DOMXPath $xpath, Company $company): ?array
    {
        try {
            // Extract title
            $titleNode = $xpath->query('.//span[contains(@class, "tcl-article-teaser__heading")]', $postNode)->item(0);
            if (!$titleNode) {
                Log::warning("No title found for Tesla post");
                return null;
            }

            $title = trim($titleNode->textContent);
            
            if (!$title) {
                Log::warning("Empty title for Tesla post");
                return null;
            }

            // Extract date
            $dateNode = $xpath->query('.//span[contains(@class, "tcl-article-teaser__published-date")]', $postNode)->item(0);
            $publishedAt = null;
            
            if ($dateNode) {
                $dateText = trim($dateNode->textContent);
                try {
                    // Parse date from "The Tesla Team, October 27, 2024" format
                    $dateText = preg_replace('/^The Tesla Team,\s*/', '', $dateText);
                    $publishedAt = Carbon::parse($dateText);
                } catch (\Exception $e) {
                    Log::warning("Could not parse Tesla date: {$dateText}");
                    $publishedAt = now();
                }
            } else {
                $publishedAt = now();
            }

            // Extract content from article summary
            $contentNode = $xpath->query('.//div[contains(@class, "tcl-article-teaser__article-summary")]', $postNode)->item(0);
            $content = '';
            
            if ($contentNode) {
                $content = $this->getNodeText($contentNode);
            }

            // Extract link
            $linkNode = $xpath->query('.//a[contains(@class, "tds-link")]', $postNode)->item(0);
            $relativeUrl = '';
            
            if ($linkNode) {
                $relativeUrl = $linkNode->getAttribute('href');
            }

            // Build full URL
            $fullUrl = $this->buildFullUrl($relativeUrl, $company->url);
            
            // Create external ID from URL or title
            $externalId = $this->createExternalId($relativeUrl ?: $title);

            return [
                'title' => $title,
                'content' => $content,
                'url' => $fullUrl,
                'published_at' => $publishedAt,
                'external_id' => $externalId,
            ];

        } catch (\Exception $e) {
            Log::error("Error extracting Tesla post data: " . $e->getMessage());
            return null;
        }
    }

    private function getNodeText(\DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                // Skip the "Continue Reading" link
                if ($child->tagName === 'a' && str_contains($child->getAttribute('class'), 'tds-link')) {
                    continue;
                }
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

    private function createExternalId(string $identifier): string
    {
        // Create a unique ID from the URL or title
        return 'tesla_' . md5($identifier);
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
