<?php

namespace App\Console\Commands;

use App\Services\StockPriceService;
use Illuminate\Console\Command;

class FetchStockPricesCommand extends Command
{
    protected $signature = 'stocks:fetch';
    protected $description = 'Fetch stock prices for all active companies';

    public function handle(StockPriceService $stockPriceService)
    {
        $this->info('Starting stock price fetch...');
        
        $stockPriceService->fetchAllStockPrices();
        
        $this->info('Stock price fetch completed.');
    }
}
