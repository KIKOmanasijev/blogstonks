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

class SealsqScraper
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
            Log::info("Starting SEALSQ scraping for {$company->name}");

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

            // Find all article rows
            $articleNodes = $xpath->query('//div[contains(@class, "article-row")]');
            
            Log::info("Found " . $articleNodes->length . " articles on SEALSQ blog");

            $results['total_posts'] = $articleNodes->length;

            foreach ($articleNodes as $index => $articleNode) {
                try {
                    $postData = $this->extractPostData($xpath, $articleNode, $company);
                    if ($postData) {
                        $saved = $this->savePost($postData, $company);
                        if ($saved) {
                            $results['new_posts']++;
                            // Classify the post after saving
                            $this->classifyPost($postData, $company);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing SEALSQ article {$index}: " . $e->getMessage());
                    $results['errors'][] = "Article {$index}: " . $e->getMessage();
                }
            }

            Log::info("SEALSQ scraping completed: {$results['new_posts']} new posts, {$results['total_posts']} total posts");

        } catch (\Exception $e) {
            Log::error("SEALSQ scraping failed: " . $e->getMessage());
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    private function extractPostData(DOMXPath $xpath, $articleNode, Company $company): ?array
    {
        try {
            // Get the link to the individual article
            $linkNode = $xpath->query('.//div[contains(@class, "buttons-container")]//a', $articleNode)->item(0);
            if (!$linkNode) {
                Log::warning("No link found for SEALSQ article");
                return null;
            }

            $articleUrl = $linkNode->getAttribute('href');
            if (!$articleUrl) {
                Log::warning("No URL found for SEALSQ article link");
                return null;
            }

            // Make sure it's a full URL
            if (strpos($articleUrl, 'http') !== 0) {
                $articleUrl = 'https://www.sealsq.com' . $articleUrl;
            }

            // Fetch the individual article page
            $articleResponse = $this->fetchContent($articleUrl);
            if (!$articleResponse) {
                Log::warning("Failed to fetch individual SEALSQ article: {$articleUrl}");
                return null;
            }

            // Parse the individual article
            $articleDom = new DOMDocument();
            libxml_use_internal_errors(true);
            $articleDom->loadHTML($articleResponse);
            libxml_clear_errors();

            $articleXpath = new DOMXPath($articleDom);

            // Extract title
            $titleNode = $articleXpath->query('//h1//span')->item(0);
            if (!$titleNode) {
                Log::warning("No title found for SEALSQ article: {$articleUrl}");
                return null;
            }
            $title = trim($titleNode->textContent);

            // Extract content
            $contentNode = $articleXpath->query('//div[contains(@class, "blog-post__body")]//span')->item(0);
            $content = $contentNode ? trim($contentNode->textContent) : '';

            // Try to extract date from the article (SEALSQ might have date info)
            $publishedAt = $this->extractDate($articleXpath, $articleDom);

            return [
                'title' => $title,
                'content' => $content,
                'url' => $articleUrl,
                'published_at' => $publishedAt,
                'company_id' => $company->id,
            ];

        } catch (\Exception $e) {
            Log::error("Error extracting SEALSQ post data: " . $e->getMessage());
            return null;
        }
    }

    private function extractDate(DOMXPath $xpath, DOMDocument $dom): ?Carbon
    {
        try {
            // Try various date selectors that might be used on SEALSQ
            $dateSelectors = [
                '//time[@datetime]',
                '//span[contains(@class, "date")]',
                '//div[contains(@class, "date")]',
                '//p[contains(@class, "date")]',
                '//meta[@property="article:published_time"]',
                '//meta[@name="date"]'
            ];

            foreach ($dateSelectors as $selector) {
                $dateNode = $xpath->query($selector)->item(0);
                if ($dateNode) {
                    $dateValue = $dateNode->getAttribute('datetime') ?: $dateNode->getAttribute('content') ?: $dateNode->textContent;
                    if ($dateValue) {
                        $date = Carbon::parse(trim($dateValue));
                        if ($date->isValid()) {
                            return $date;
                        }
                    }
                }
            }

            // If no date found, return current time
            return Carbon::now();

        } catch (\Exception $e) {
            Log::warning("Could not extract date from SEALSQ article: " . $e->getMessage());
            return Carbon::now();
        }
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
                // Don't mark as notified here - let classification handle notifications
            ]);

            Log::info("SEALSQ post saved: {$post->title}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error saving SEALSQ post: " . $e->getMessage());
            return false;
        }
    }

    private function classifyPost(array $postData, Company $company): void
    {
        try {
            // Get the saved post
            $post = Post::where('url', $postData['url'])->first();
            if (!$post) {
                Log::warning("Could not find saved SEALSQ post for classification: {$postData['url']}");
                return;
            }

            // Only classify if not already classified
            if (!$post->isClassified()) {
                $post->classify();
            }

        } catch (\Exception $e) {
            Log::error("Error classifying SEALSQ post: " . $e->getMessage());
        }
    }

    private function fetchContent(string $url): ?string
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
                Log::error("Failed to fetch SEALSQ content: HTTP {$response->status()}", ['url' => $url]);
                return null;
            }

            return $response->body();

        } catch (\Exception $e) {
            Log::error('SEALSQ fetch exception: ' . $e->getMessage(), ['url' => $url]);
            return null;
        }
    }
}
