<?php

namespace App\Services;

use App\Models\Company;
use App\Models\StockPrice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StockPriceService
{
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.finnhub.api_key');
    }

    public function fetchAllStockPrices(): void
    {
        $companies = Company::where('is_active', true)
            ->whereNotNull('ticker')
            ->get();

        foreach ($companies as $company) {
            $this->fetchStockPrice($company);
        }
    }

    public function fetchStockPrice(Company $company): void
    {
        if (!$this->apiKey) {
            Log::warning('Finnhub API key not configured, skipping stock price fetch');
            return;
        }

        if (!$company->ticker) {
            Log::warning("No ticker found for company: {$company->name}");
            return;
        }

        try {
            $response = Http::timeout(30)->get('https://finnhub.io/api/v1/quote', [
                'symbol' => $company->ticker,
                'token' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                Log::error("Failed to fetch stock price for {$company->ticker}: HTTP {$response->status()}");
                return;
            }

            $data = $response->json();

            if (isset($data['error'])) {
                Log::error("Finnhub API error for {$company->ticker}: {$data['error']}");
                return;
            }

            if (empty($data) || !isset($data['c'])) {
                Log::warning("No stock data found for ticker: {$company->ticker}");
                return;
            }

            $this->storeStockPrice($company, $data);

        } catch (\Exception $e) {
            Log::error("Exception while fetching stock price for {$company->ticker}: " . $e->getMessage());
        }
    }

    private function storeStockPrice(Company $company, array $quote): void
    {
        try {
            $priceAt = now();

            // Check if we already have a price for this exact minute to avoid duplicates
            $existingPrice = StockPrice::where('company_id', $company->id)
                ->where('price_at', '>=', $priceAt->copy()->startOfMinute())
                ->where('price_at', '<=', $priceAt->copy()->endOfMinute())
                ->first();

            if ($existingPrice) {
                Log::info("Stock price already exists for {$company->ticker} at {$priceAt->format('Y-m-d H:i:s')}");
                return;
            }

            $stockPrice = StockPrice::create([
                'company_id' => $company->id,
                'price' => $this->parseNumericValue($quote['c']), // current price
                'open' => $this->parseNumericValue($quote['o']), // open price
                'high' => $this->parseNumericValue($quote['h']), // high price
                'low' => $this->parseNumericValue($quote['l']), // low price
                'close' => $this->parseNumericValue($quote['pc']), // previous close
                'volume' => null, // Finnhub quote doesn't include volume
                'change' => $this->parseNumericValue($quote['d']), // change
                'change_percent' => $this->parseNumericValue($quote['dp']), // change percent
                'price_at' => $priceAt,
            ]);

            Log::info("Stock price stored for {$company->ticker}: {$stockPrice->price}");

        } catch (\Exception $e) {
            Log::error("Failed to store stock price for {$company->ticker}: " . $e->getMessage());
        }
    }

    private function parseNumericValue($value): ?float
    {
        if (is_null($value) || $value === '' || $value === 'N/A') {
            return null;
        }

        // Finnhub returns numeric values directly, but handle string cases too
        if (is_string($value)) {
            return (float) str_replace(',', '', $value);
        }

        return (float) $value;
    }
}
