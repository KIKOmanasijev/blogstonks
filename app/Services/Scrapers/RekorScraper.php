<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Post;
use App\Services\TelegramNotificationService;
use App\Services\ProxyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RekorScraper
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
                Log::warning("Proxy failed, trying direct request for Rekor blog");
                $html = $this->proxyService->fetchDirect($company->blog_url);
            }
            
            if (!$html) {
                Log::error("Failed to fetch Rekor blog with both proxy and direct methods");
                return;
            }
            
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Find all blog post containers using the provided selector
            $postNodes = $xpath->query('//div[contains(@class, "blog-list")]//div[contains(@class, "collection-item")]');
            
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
                    Log::error("Error processing Rekor post: " . $e->getMessage());
                    continue;
                }
            }

            // Update last scraped timestamp
            $company->update(['last_scraped_at' => now()]);
            
            Log::info("Rekor scraping completed. Scraped {$scrapedCount} posts, {$newPostsCount} new.");
            
        } catch (\Exception $e) {
            Log::error("Rekor scraping failed: " . $e->getMessage());
        }
    }

    private function extractPostData(\DOMElement $postNode, \DOMXPath $xpath, Company $company): ?array
    {
        try {
            // Extract title from h3.blog-title
            $titleNode = $xpath->query('.//h3[contains(@class, "blog-title")]', $postNode)->item(0);
            if (!$titleNode) {
                Log::warning("No title found for Rekor post");
                return null;
            }

            $title = trim($titleNode->textContent);
            
            if (!$title) {
                Log::warning("Empty title for Rekor post");
                return null;
            }

            // Extract date from the text-size-small div
            $dateNode = $xpath->query('.//div[contains(@class, "text-size-small")]', $postNode)->item(0);
            $publishedAt = null;
            
            if ($dateNode) {
                $dateText = trim($dateNode->textContent);
                try {
                    // Parse date from "August 21, 2025" format
                    $publishedAt = Carbon::parse($dateText);
                } catch (\Exception $e) {
                    Log::warning("Could not parse Rekor date: {$dateText}");
                    $publishedAt = now();
                }
            } else {
                $publishedAt = now();
            }

            // Extract content from the text-size-small paragraph
            $contentNode = $xpath->query('.//p[contains(@class, "text-size-small")]', $postNode)->item(0);
            $content = '';
            
            if ($contentNode) {
                $content = $this->getNodeText($contentNode);
            }

            // Extract link from the blog-item link
            $linkNode = $xpath->query('.//a[contains(@class, "blog-item")]', $postNode)->item(0);
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
            Log::error("Error extracting Rekor post data: " . $e->getMessage());
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
        return 'rekor_' . md5($identifier);
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
     * Scrape individual blog post for detailed content
     */
    public function scrapeIndividualPost(string $postUrl): ?array
    {
        try {
            $html = $this->proxyService->fetchWithProxy($postUrl);
            
            if (!$html) {
                Log::warning("Proxy failed, trying direct request for Rekor individual post");
                $html = $this->proxyService->fetchDirect($postUrl);
            }
            
            if (!$html) {
                Log::error("Failed to fetch Rekor individual post with both proxy and direct methods");
                return null;
            }
            
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Extract title from h1
            $titleNode = $xpath->query('//h1')->item(0);
            $title = $titleNode ? trim($titleNode->textContent) : '';

            // Extract publish date from p.publish-date
            $dateNode = $xpath->query('//p[contains(@class, "publish-date")]')->item(0);
            $publishedAt = null;
            
            if ($dateNode) {
                $dateText = trim($dateNode->textContent);
                try {
                    $publishedAt = Carbon::parse($dateText);
                } catch (\Exception $e) {
                    Log::warning("Could not parse Rekor individual post date: {$dateText}");
                    $publishedAt = now();
                }
            } else {
                $publishedAt = now();
            }

            // Extract content from div.article
            $contentNode = $xpath->query('//div[contains(@class, "article")]')->item(0);
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
            Log::error("Error scraping Rekor individual post: " . $e->getMessage());
            return null;
        }
    }
}
