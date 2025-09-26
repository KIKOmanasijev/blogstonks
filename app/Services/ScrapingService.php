<?php

namespace App\Services;

use App\Models\Company;
use App\Services\Scrapers\IonqScraper;
use App\Services\Scrapers\DwaveScraper;
use App\Services\Scrapers\TeslaScraper;
use App\Services\Scrapers\QciScraper;
use App\Services\Scrapers\RigettiScraper;
use App\Services\Scrapers\IntelScraper;
use App\Services\Scrapers\SealsqScraper;
use App\Services\Scrapers\AmdScraper;
use App\Services\Scrapers\CifrScraper;
use App\Services\Scrapers\LciScraper;
use App\Services\Scrapers\RekorScraper;
use App\Services\Scrapers\NuvveScraper;
use App\Services\ProxyService;
use Illuminate\Support\Facades\Log;

class ScrapingService
{
    protected array $scrapers = [
        'ionq' => IonqScraper::class,
        'dwave' => DwaveScraper::class,
        'tesla' => TeslaScraper::class,
        'qci' => QciScraper::class,
        'rigetti' => RigettiScraper::class,
        'intel' => IntelScraper::class,
        'sealsq' => SealsqScraper::class,
        'amd' => AmdScraper::class,
        'cifr' => CifrScraper::class,
        'lci' => LciScraper::class,
        'rekor' => RekorScraper::class,
        'nuvve' => NuvveScraper::class,
    ];

    protected TelegramNotificationService $telegramService;
    protected ProxyService $proxyService;

    public function __construct(TelegramNotificationService $telegramService, ProxyService $proxyService)
    {
        $this->telegramService = $telegramService;
        $this->proxyService = $proxyService;
    }

    public function scrapeAllCompanies(): void
    {
        $companies = Company::where('is_active', true)->get();
        
        foreach ($companies as $company) {
            $this->scrapeCompany($company);
        }
    }

    public function scrapeCompany(Company $company): void
    {
        try {
            $scraperClass = $this->getScraperForCompany($company);
            
            if (!$scraperClass) {
                Log::warning("No scraper found for company: {$company->name}");
                return;
            }

            // Inject dependencies based on scraper type
            if ($scraperClass === TeslaScraper::class || $scraperClass === RekorScraper::class || $scraperClass === NuvveScraper::class) {
                $scraper = new $scraperClass($this->telegramService, $this->proxyService);
            } else {
                $scraper = new $scraperClass($this->telegramService);
            }
            $scraper->scrape($company);
            
        } catch (\Exception $e) {
            Log::error("Failed to scrape company {$company->name}: " . $e->getMessage());
        }
    }

    private function getScraperForCompany(Company $company): ?string
    {
        // Determine scraper based on company URL or name
        $url = strtolower($company->url);
        $name = strtolower($company->name);
        
        if (str_contains($url, 'ionq.com')) {
            return $this->scrapers['ionq'];
        }
        
        if (str_contains($url, 'dwavequantum.com') || str_contains($name, 'd-wave')) {
            return $this->scrapers['dwave'];
        }
        
        if (str_contains($url, 'tesla.com') || str_contains($name, 'tesla')) {
            return $this->scrapers['tesla'];
        }
        
        if (str_contains($url, 'quantumcomputinginc.com') || str_contains($name, 'quantum computing inc')) {
            return $this->scrapers['qci'];
        }
        
        if (str_contains($url, 'rigetti.com') || str_contains($name, 'rigetti')) {
            return $this->scrapers['rigetti'];
        }
        
        if (str_contains($url, 'intel.com') || str_contains($name, 'intel')) {
            return $this->scrapers['intel'];
        }
        
        if (str_contains($url, 'sealsq.com') || str_contains($name, 'sealsq')) {
            return $this->scrapers['sealsq'];
        }
        
        if (str_contains($url, 'amd.com') || str_contains($name, 'amd') || str_contains($name, 'advanced micro devices')) {
            return $this->scrapers['amd'];
        }
        
        if (str_contains($url, 'ciphermining.com') || str_contains($name, 'cipher') || str_contains($name, 'cifr')) {
            return $this->scrapers['cifr'];
        }
        
        if (str_contains($url, 'standardlithium.com') || str_contains($name, 'standard lithium') || str_contains($name, 'lci')) {
            return $this->scrapers['lci'];
        }
        
        if (str_contains($url, 'rekor.ai') || str_contains($name, 'rekor') || str_contains($name, 'rekor systems')) {
            return $this->scrapers['rekor'];
        }
        
        if (str_contains($url, 'investors.nuvve.com') || str_contains($name, 'nuvve') || str_contains($name, 'nuvve holding')) {
            return $this->scrapers['nuvve'];
        }
        
        return null;
    }
}
