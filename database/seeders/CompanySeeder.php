<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'name' => 'IonQ',
            'url' => 'https://ionq.com',
            'blog_url' => 'https://ionq.com/blog',
            'favicon_url' => 'https://companieslogo.com/img/orig/IONQ-f072dd64.png?t=1720244492',
            'ticker' => 'IONQ',
            'title_selector' => '.resources-item-title',
            'content_selector' => 'article, .content, .post-content',
            'date_selector' => '.resources-item-date',
            'link_selector' => '.resources-panel',
            'is_active' => true,
        ]);

        Company::create([
            'name' => 'D-Wave Quantum',
            'url' => 'https://www.dwavequantum.com',
            'blog_url' => 'https://www.dwavequantum.com/company/newsroom/',
            'favicon_url' => 'https://www.hpcwire.com/wp-content/uploads/2024/02/dwave-d-wave-400x400-1.jpg',
            'ticker' => 'QBTS',
            'title_selector' => '.cp-news-item-teaser h3 a',
            'content_selector' => '.article-body',
            'date_selector' => '.eyebrow__label',
            'link_selector' => '.cp-news-item-teaser h3 a',
            'is_active' => true,
        ]);

        Company::create([
            'name' => 'Tesla',
            'url' => 'https://www.tesla.com',
            'blog_url' => 'https://www.tesla.com/blog',
            'favicon_url' => 'https://s3-symbol-logo.tradingview.com/tesla--600.png',
            'ticker' => 'TSLA',
            'title_selector' => '.tcl-article-teaser__heading',
            'content_selector' => '.tcl-article-teaser__article-summary',
            'date_selector' => '.tcl-article-teaser__published-date',
            'link_selector' => '.tcl-article-teaser__article-summary .tds-link',
            'is_active' => false, // Temporarily disabled due to bot protection
        ]);

        Company::create([
            'name' => 'Quantum Computing Inc.',
            'url' => 'https://quantumcomputinginc.com',
            'blog_url' => 'https://quantumcomputinginc.com/news',
            'favicon_url' => 'https://trading212equities.s3.eu-central-1.amazonaws.com/QUBT_US_EQ.png',
            'ticker' => 'QUBT',
            'title_selector' => '.MuiTypography-h4',
            'content_selector' => '.paperTemplate_paper__tvabE',
            'date_selector' => '.MuiTypography-h5',
            'link_selector' => '.MuiButton-root',
            'is_active' => true,
        ]);
    }
}
