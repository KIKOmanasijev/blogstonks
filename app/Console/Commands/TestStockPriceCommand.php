<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\StockPriceService;
use Illuminate\Console\Command;

class TestStockPriceCommand extends Command
{
    protected $signature = 'test:stock-price';
    protected $description = 'Test stock price fetching functionality';

    public function handle(StockPriceService $stockPriceService)
    {
        if (!config('services.finnhub.api_key')) {
            $this->error('FINNHUB_API_KEY must be configured in your .env file');
            $this->info('Get your free API key at: https://finnhub.io/register');
            return 1;
        }

        $company = Company::whereNotNull('ticker')->first();
        if (!$company) {
            $this->error('No companies with tickers found in database.');
            return 1;
        }

        $this->info("Testing stock price fetch for {$company->name} ({$company->ticker})...");
        
        $stockPriceService->fetchStockPrice($company);
        
        $latestPrice = $company->getLatestStockPrice();
        if ($latestPrice) {
            $this->info("✅ Stock price fetched successfully!");
            $this->info("Price: $" . number_format($latestPrice->price, 2));
            if ($latestPrice->change_percent) {
                $this->info("Change: " . ($latestPrice->change_percent >= 0 ? '+' : '') . number_format($latestPrice->change_percent, 2) . "%");
            }
            $this->info("Fetched at: " . $latestPrice->price_at->format('Y-m-d H:i:s'));
        } else {
            $this->error("❌ Failed to fetch stock price. Check logs for details.");
            return 1;
        }

        return 0;
    }
}
