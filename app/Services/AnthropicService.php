<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicService
{
    protected ?string $apiKey;
    protected string $baseUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
    }

    public function classifyPost(string $title, ?string $description = null): ?array
    {
        if (!$this->apiKey) {
            Log::warning('Anthropic API key not configured, skipping post classification');
            return null;
        }

        try {
            $prompt = $this->buildClassificationPrompt($title, $description);
            
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, [
                    'model' => 'claude-3-5-haiku-20241022',
                    'max_tokens' => 1000,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if (!$response->successful()) {
                Log::error("Anthropic API request failed: HTTP {$response->status()}");
                return null;
            }

            $data = $response->json();
            
            if (!isset($data['content'][0]['text'])) {
                Log::error("Unexpected Anthropic API response format");
                return null;
            }

            $responseText = $data['content'][0]['text'];
            $classification = $this->parseClassificationResponse($responseText);
            
            if ($classification) {
                Log::info("Post classified: huge={$classification['huge']}, score={$classification['score']}");
            }

            return $classification;

        } catch (\Exception $e) {
            Log::error("Exception while classifying post: " . $e->getMessage());
            return null;
        }
    }

    private function buildClassificationPrompt(string $title, ?string $description = null): string
    {
        $prompt = "You are an expert business news analyst.  
You are given the **title** and an optional **description** of a company blog post.  
Your task is to determine if the post qualifies as **HUGE news**. 
You MUST be as objective as possible. It should be very hard to get a high grade. 
Only give a high grade if you are absolutely sure there can not be bias in the post (such as product announcements, technology breakthroughs, research collaborations, roadmap announcements, hiring news, or acquisitions without disclosed deal sizes).

HUGE news is defined strictly as corporate events with clear and measurable financial impact, such as:  
- Investments or funding rounds above **$500 million**  
- Acquisitions or mergers of major competitors (with disclosed or implied deal values in the billions)  
- Government contracts worth more than **$5 million**  
- Major partnership with the Big Tech companies (Tech Giants, FAANG, and etc) in tech USA.
- IPO announcements or listings on major exchanges

Ignore general product updates, technology breakthroughs, research collaborations, roadmap announcements, hiring news, or acquisitions without disclosed deal sizes. These do **not** qualify as huge news.  

Return only a JSON object in the following format:   

```json
{
  \"huge\": true/false,
  \"score\": 0 to 100 value,
  \"reasoning\": a short explanation of why you gave the score you did
}
```

Title: {$title}";

        if ($description) {
            $prompt .= "\n\nDescription: {$description}";
        }

        return $prompt;
    }

    private function parseClassificationResponse(string $responseText): ?array
    {
        try {
            // Try to extract JSON from the response
            $jsonStart = strpos($responseText, '{');
            $jsonEnd = strrpos($responseText, '}');
            
            if ($jsonStart === false || $jsonEnd === false) {
                Log::error("No JSON found in Anthropic response");
                return null;
            }

            $jsonString = substr($responseText, $jsonStart, $jsonEnd - $jsonStart + 1);
            $classification = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Failed to parse JSON from Anthropic response: " . json_last_error_msg());
                return null;
            }

            // Validate the response structure
            if (!isset($classification['huge']) || !isset($classification['score'])) {
                Log::error("Invalid classification response structure");
                return null;
            }

            // Ensure score is within valid range
            $classification['score'] = max(0, min(100, (int) $classification['score']));
            $classification['huge'] = (bool) $classification['huge'];

            return $classification;

        } catch (\Exception $e) {
            Log::error("Error parsing classification response: " . $e->getMessage());
            return null;
        }
    }
}
