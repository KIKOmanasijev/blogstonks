<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyService
{
    protected ?string $apiKey;
    protected string $service = 'scrapingbee'; // Default service

    public function __construct()
    {
        $this->apiKey = config('services.proxy.scrapingbee_api_key');
    }

    public function fetchWithProxy(string $url, array $options = []): ?string
    {
        if (!$this->apiKey) {
            Log::warning('Proxy API key not configured, falling back to direct request');
            return $this->fetchDirect($url);
        }

        try {
            $response = Http::timeout(60)->get('https://app.scrapingbee.com/api/v1/', array_merge([
                'api_key' => $this->apiKey,
                'url' => $url,
                'render_js' => 'true', // Enable JavaScript rendering
                'premium_proxy' => 'true', // Use premium proxies
                'country_code' => 'us', // Use US proxies
                'wait' => 5000, // Wait 5 seconds for page to load
                'block_resources' => 'false', // Don't block resources
                'stealth_proxy' => 'true', // Use stealth mode
            ], $options));

            if (!$response->successful()) {
                Log::error("Proxy request failed: HTTP {$response->status()}", [
                    'url' => $url,
                    'response' => $response->body()
                ]);
                return null;
            }

            Log::info('Proxy request successful', ['url' => $url]);
            return $response->body();

        } catch (\Exception $e) {
            Log::error('Proxy request exception: ' . $e->getMessage(), ['url' => $url]);
            return null;
        }
    }

    public function fetchDirect(string $url): ?string
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
                Log::error("Direct request failed: HTTP {$response->status()}", ['url' => $url]);
                return null;
            }

            return $response->body();

        } catch (\Exception $e) {
            Log::error('Direct request exception: ' . $e->getMessage(), ['url' => $url]);
            return null;
        }
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== null;
    }
}
