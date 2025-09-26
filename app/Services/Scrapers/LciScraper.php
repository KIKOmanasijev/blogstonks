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

class LciScraper
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
            Log::info("Starting LCI scraping for {$company->name}");

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

            // Find all blog cards using the specified selector
            $blogNodes = $xpath->query('//div[contains(@class, "content")]//div[contains(@class, "media")]');
            
            Log::info("Found " . $blogNodes->length . " blog cards on LCI press releases");

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
                    Log::error("Error processing LCI blog card {$index}: " . $e->getMessage());
                    $results['errors'][] = "Blog card {$index}: " . $e->getMessage();
                }
            }

            Log::info("LCI scraping completed: {$results['new_posts']} new posts, {$results['total_posts']} total posts");

        } catch (\Exception $e) {
            Log::error("LCI scraping failed: " . $e->getMessage());
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    private function extractPostData(DOMXPath $xpath, $blogNode, Company $company): ?array
    {
        try {
            // Get the link to the individual article
            $linkNode = $xpath->query('.//h2[contains(@class, "media-heading")]//a', $blogNode)->item(0);
            if (!$linkNode) {
                Log::warning("No link found for LCI blog card");
                return null;
            }

            $articleUrl = $linkNode->getAttribute('href');
            if (!$articleUrl) {
                Log::warning("No URL found for LCI blog card link");
                return null;
            }

            // Make sure it's a full URL
            if (strpos($articleUrl, 'http') !== 0) {
                $articleUrl = 'https://www.standardlithium.com' . $articleUrl;
            }

            // Extract title from the card
            $titleNode = $xpath->query('.//h2[contains(@class, "media-heading")]//a', $blogNode)->item(0);
            if (!$titleNode) {
                Log::warning("No title found for LCI blog card");
                return null;
            }
            $title = trim($titleNode->textContent);

            // Extract date from the card
            $dateNode = $xpath->query('.//div[contains(@class, "date")]//time', $blogNode)->item(0);
            $publishedAt = $this->extractDate($dateNode);

            // Fetch the individual article page for full content
            $articleResponse = $this->fetchContent($articleUrl);
            if (!$articleResponse) {
                Log::warning("Failed to fetch individual LCI article: {$articleUrl}");
                // Still return the post data with basic info
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

            // Extract content from the article page using the specified selector
            $contentNode = $articleXpath->query('//div[contains(@class, "full-news-article")]')->item(0);
            $content = $contentNode ? $this->extractTextContent($contentNode) : '';

            return [
                'title' => $title,
                'content' => $content,
                'url' => $articleUrl,
                'published_at' => $publishedAt,
                'company_id' => $company->id,
            ];

        } catch (\Exception $e) {
            Log::error("Error extracting LCI post data: " . $e->getMessage());
            return null;
        }
    }

    private function extractDate($dateNode): ?Carbon
    {
        try {
            if ($dateNode) {
                // Try to get the datetime attribute first
                $dateValue = $dateNode->getAttribute('datetime');
                if (!$dateValue) {
                    // Fall back to text content
                    $dateValue = trim($dateNode->textContent);
                }
                
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
            Log::warning("Could not extract date from LCI article: " . $e->getMessage());
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
                // Don't mark as notified here - let classification handle notifications
            ]);

            Log::info("LCI post saved: {$post->title}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error saving LCI post: " . $e->getMessage());
            return false;
        }
    }

    private function classifyPost(array $postData, Company $company): void
    {
        try {
            // Get the saved post
            $post = Post::where('url', $postData['url'])->first();
            if (!$post) {
                Log::warning("Could not find saved LCI post for classification: {$postData['url']}");
                return;
            }

            // Only classify if not already classified
            if (!$post->isClassified()) {
                $post->classify();
            }

        } catch (\Exception $e) {
            Log::error("Error classifying LCI post: " . $e->getMessage());
        }
    }

    private function fetchContent(string $url): ?string
    {
        try {
            $response = Http::get($url);

            if (!$response->successful()) {
                Log::error("Failed to fetch LCI content: HTTP {$response->status()}", ['url' => $url]);
                return null;
            }

            return $response->body();

        } catch (\Exception $e) {
            Log::error('LCI fetch exception: ' . $e->getMessage(), ['url' => $url]);
            return null;
        }
    }
}
