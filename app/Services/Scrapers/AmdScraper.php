<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Post;
use App\Services\TelegramNotificationService;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmdScraper
{
    protected TelegramNotificationService $telegramService;

    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function scrape(Company $company): array
    {
        $results = [
            'new_posts' => 0,
            'total_posts' => 0,
            'errors' => []
        ];

        try {
            Log::info("Starting AMD scraping for {$company->name}");

            // Get the blog URL
            $blogUrl = $company->blog_url;
            if (!$blogUrl) {
                throw new \Exception("No blog URL configured for {$company->name}");
            }

            // Fetch the page content
            $response = $this->fetchContent($blogUrl);
            if (!$response) {
                throw new \Exception("Failed to fetch content from {$blogUrl}");
            }

            // Parse the HTML
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($response);
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);

            // Find all blog cards in the glide slides - AMD uses <ul> and <li> structure
            $blogNodes = $xpath->query('//ul[contains(@class, "glide__slides")]//li[contains(@class, "glide__slide")]//div[contains(@class, "related-content-card")]');
            
            // If no nodes found, try alternative selectors for AMD
            if ($blogNodes->length === 0) {
                Log::info("No glide slides found, trying alternative AMD selectors");
                $blogNodes = $xpath->query('//div[contains(@class, "related-content-card")]');
            }
            
            Log::info("Found " . $blogNodes->length . " blog cards on AMD newsroom");

            $results['total_posts'] = $blogNodes->length;

            foreach ($blogNodes as $index => $blogNode) {
                try {
                    $postData = $this->extractPostData($xpath, $blogNode, $company);
                    if ($postData) {
                        $saved = $this->savePost($postData, $company);
                        if ($saved) {
                            $results['new_posts']++;
                            // Classify the post after saving
                            $this->classifyPost($postData, $company);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing AMD blog card {$index}: " . $e->getMessage());
                    $results['errors'][] = "Blog card {$index}: " . $e->getMessage();
                }
            }

            Log::info("AMD scraping completed: {$results['new_posts']} new posts, {$results['total_posts']} total posts");

        } catch (\Exception $e) {
            Log::error("AMD scraping failed: " . $e->getMessage());
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    private function extractPostData(DOMXPath $xpath, $blogNode, Company $company): ?array
    {
        try {
            // Get the link to the individual article - AMD uses specific press release URLs
            $linkNode = $xpath->query('.//a[contains(@href, "/en/newsroom/press-releases/")]', $blogNode)->item(0);
            if (!$linkNode) {
                Log::warning("No link found for AMD blog card");
                return null;
            }

            $articleUrl = $linkNode->getAttribute('href');
            if (!$articleUrl) {
                Log::warning("No URL found for AMD blog card link");
                return null;
            }

            // Make sure it's a full URL
            if (strpos($articleUrl, 'http') !== 0) {
                $articleUrl = 'https://www.amd.com' . $articleUrl;
            }

            // Extract title from the card
            $titleNode = $xpath->query('.//h5[contains(@class, "card-title")]//a', $blogNode)->item(0);
            if (!$titleNode) {
                Log::warning("No title found for AMD blog card");
                return null;
            }
            $title = trim($titleNode->textContent);

            // Extract date from the card
            $dateNode = $xpath->query('.//p[contains(@class, "card-date")]', $blogNode)->item(0);
            $publishedAt = $this->extractDate($dateNode);

            // Fetch the individual article page for content
            $articleResponse = $this->fetchContent($articleUrl);
            if (!$articleResponse) {
                Log::warning("Failed to fetch individual AMD article: {$articleUrl}");
                // Still return the post data even if we can't get the full content
                return [
                    'title' => $title,
                    'content' => '',
                    'url' => $articleUrl,
                    'published_at' => $publishedAt,
                    'company_id' => $company->id,
                ];
            }

            // Parse the individual article
            $articleDom = new DOMDocument();
            libxml_use_internal_errors(true);
            $articleDom->loadHTML($articleResponse);
            libxml_clear_errors();

            $articleXpath = new DOMXPath($articleDom);

            // Extract content from the article page
            $contentNode = $articleXpath->query('//div[contains(@class, "cmp-container__content")]')->item(0);
            $content = $contentNode ? $this->extractTextContent($contentNode) : '';

            return [
                'title' => $title,
                'content' => $content,
                'url' => $articleUrl,
                'published_at' => $publishedAt,
                'company_id' => $company->id,
            ];

        } catch (\Exception $e) {
            Log::error("Error extracting AMD post data: " . $e->getMessage());
            return null;
        }
    }

    private function extractDate($dateNode): ?Carbon
    {
        try {
            if ($dateNode) {
                $dateValue = trim($dateNode->textContent);
                if ($dateValue) {
                    $date = Carbon::parse($dateValue);
                    if ($date->isValid()) {
                        return $date;
                    }
                }
            }

            // If no date found, return current time
            return Carbon::now();

        } catch (\Exception $e) {
            Log::warning("Could not extract date from AMD article: " . $e->getMessage());
            return Carbon::now();
        }
    }

    private function extractTextContent($node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $text .= $this->extractTextContent($child) . ' ';
            }
        }
        return trim($text);
    }

    private function savePost(array $postData, Company $company): bool
    {
        try {
            // Check if post already exists
            $existingPost = Post::where('url', $postData['url'])->first();
            if ($existingPost) {
                return false; // Post already exists
            }

            // Create new post
            $post = Post::create([
                'title' => $postData['title'],
                'content' => $postData['content'],
                'url' => $postData['url'],
                'published_at' => $postData['published_at'],
                'company_id' => $postData['company_id'],
                'user_notified_at' => now(), // Mark as notified since we're processing it
            ]);

            Log::info("AMD post saved: {$post->title}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error saving AMD post: " . $e->getMessage());
            return false;
        }
    }

    private function classifyPost(array $postData, Company $company): void
    {
        try {
            // Get the saved post
            $post = Post::where('url', $postData['url'])->first();
            if (!$post) {
                Log::warning("Could not find saved AMD post for classification: {$postData['url']}");
                return;
            }

            // Only classify if not already classified
            if (!$post->isClassified()) {
                $post->classify();
            }

        } catch (\Exception $e) {
            Log::error("Error classifying AMD post: " . $e->getMessage());
        }
    }

    private function fetchContent(string $url): ?string
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'DNT' => '1',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::error("Failed to fetch AMD content: HTTP {$response->status()}", ['url' => $url]);
                return null;
            }

            return $response->body();

        } catch (\Exception $e) {
            Log::error('AMD fetch exception: ' . $e->getMessage(), ['url' => $url]);
            return null;
        }
    }
}
