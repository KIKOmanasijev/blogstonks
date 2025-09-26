<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Post;
use App\Services\TelegramNotificationService;
use App\Services\ProxyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NuvveScraper
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
                Log::warning("Proxy failed, trying direct request for Nuvve blog");
                $html = $this->proxyService->fetchDirect($company->blog_url);
            }
            
            if (!$html) {
                Log::error("Failed to fetch Nuvve blog with both proxy and direct methods");
                return;
            }
            
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Find all news article containers using the provided selector
            $postNodes = $xpath->query('//div[contains(@class, "nir-widget--list")]//article');
            
            $scrapedCount = 0;
            $newPostsCount = 0;
            $maxPosts = 3; // Only scrape last 3 posts

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
                    Log::error("Error processing Nuvve post: " . $e->getMessage());
                    continue;
                }
            }

            // Update last scraped timestamp
            $company->update(['last_scraped_at' => now()]);
            
            Log::info("Nuvve scraping completed. Scraped {$scrapedCount} posts, {$newPostsCount} new.");
            
        } catch (\Exception $e) {
            Log::error("Nuvve scraping failed: " . $e->getMessage());
        }
    }

    private function extractPostData(\DOMElement $postNode, \DOMXPath $xpath, Company $company): ?array
    {
        try {
            // Extract title from the headline link
            $titleNode = $xpath->query('.//div[contains(@class, "nir-widget--news--headline")]//a', $postNode)->item(0);
            if (!$titleNode) {
                Log::warning("No title found for Nuvve post");
                return null;
            }

            $title = trim($titleNode->textContent);
            
            if (!$title) {
                Log::warning("Empty title for Nuvve post");
                return null;
            }

            // Extract date from press-date and press-year
            $dateNode = $xpath->query('.//div[contains(@class, "ndq-press-date")]', $postNode)->item(0);
            $publishedAt = null;
            
            if ($dateNode) {
                $dayNode = $xpath->query('.//span[contains(@class, "press-date")]', $dateNode)->item(0);
                $yearNode = $xpath->query('.//span[contains(@class, "press-year")]', $dateNode)->item(0);
                
                if ($dayNode && $yearNode) {
                    $day = trim($dayNode->textContent);
                    $yearMonth = trim($yearNode->textContent); // Format: "Sep / 25"
                    
                    try {
                        // Parse "Sep / 25" format
                        $yearMonthParts = explode(' / ', $yearMonth);
                        if (count($yearMonthParts) === 2) {
                            $month = $yearMonthParts[0];
                            $year = '20' . $yearMonthParts[1]; // Convert "25" to "2025"
                            $dateString = "{$month} {$day}, {$year}";
                            $publishedAt = Carbon::parse($dateString);
                        } else {
                            $publishedAt = now();
                        }
                    } catch (\Exception $e) {
                        Log::warning("Could not parse Nuvve date: {$day} {$yearMonth}");
                        $publishedAt = now();
                    }
                } else {
                    $publishedAt = now();
                }
            } else {
                $publishedAt = now();
            }

            // Extract content from the teaser
            $contentNode = $xpath->query('.//div[contains(@class, "nir-widget--news--teaser")]', $postNode)->item(0);
            $content = '';
            
            if ($contentNode) {
                $content = $this->getNodeText($contentNode);
            }

            // Extract link from the headline
            $linkNode = $xpath->query('.//div[contains(@class, "nir-widget--news--headline")]//a', $postNode)->item(0);
            $relativeUrl = '';
            
            if ($linkNode instanceof \DOMElement) {
                $relativeUrl = $linkNode->getAttribute('href') ?: '';
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
            Log::error("Error extracting Nuvve post data: " . $e->getMessage());
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
        return 'nuvve_' . md5($identifier);
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

    /**
     * Scrape individual news post for detailed content
     */
    public function scrapeIndividualPost(string $postUrl): ?array
    {
        try {
            $html = $this->proxyService->fetchWithProxy($postUrl);
            
            if (!$html) {
                Log::warning("Proxy failed, trying direct request for Nuvve individual post");
                $html = $this->proxyService->fetchDirect($postUrl);
            }
            
            if (!$html) {
                Log::error("Failed to fetch Nuvve individual post with both proxy and direct methods");
                return null;
            }
            
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Extract title from h2 .field--name-field-nir-news-title .field__item
            $titleNode = $xpath->query('//h2[contains(@class, "field--name-field-nir-news-title")]//div[contains(@class, "field__item")]')->item(0);
            $title = $titleNode ? trim($titleNode->textContent) : '';

            // Extract publish date from .ndq-date > div
            $dateNode = $xpath->query('//div[contains(@class, "ndq-date")]/div')->item(0);
            $publishedAt = null;
            
            if ($dateNode) {
                $dateText = trim($dateNode->textContent);
                try {
                    $publishedAt = Carbon::parse($dateText);
                } catch (\Exception $e) {
                    Log::warning("Could not parse Nuvve individual post date: {$dateText}");
                    $publishedAt = now();
                }
            } else {
                $publishedAt = now();
            }

            // Extract content from .node__content
            $contentNode = $xpath->query('//div[contains(@class, "node__content")]')->item(0);
            $content = '';
            
            if ($contentNode) {
                $content = $this->getNodeText($contentNode);
            }

            return [
                'title' => $title,
                'content' => $content,
                'url' => $postUrl,
                'published_at' => $publishedAt,
            ];

        } catch (\Exception $e) {
            Log::error("Error scraping Nuvve individual post: " . $e->getMessage());
            return null;
        }
    }
}
