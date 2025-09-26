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
        Company::firstOrCreate([
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

        Company::firstOrCreate([
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

        Company::firstOrCreate([
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

        Company::firstOrCreate([
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

        Company::firstOrCreate([
            'name' => 'Rigetti Computing',
            'url' => 'https://www.rigetti.com',
            'blog_url' => 'https://www.rigetti.com/rigetti-computing-news',
            'favicon_url' => 'https://companieslogo.com/img/orig/RGTI-3681061a.png?t=1730913635',
            'ticker' => 'RGTI',
            'title_selector' => 'h3',
            'content_selector' => 'p',
            'date_selector' => '.date',
            'link_selector' => '.read-more',
            'is_active' => true,
        ]);

        Company::firstOrCreate([
            'name' => 'Intel Corporation',
            'url' => 'https://www.intel.com',
            'blog_url' => 'https://newsroom.intel.com',
            'favicon_url' => 'https://icon2.cleanpng.com/20180525/uoi/avqpba21h.webp',
            'ticker' => 'INTC',
            'title_selector' => 'h2',
            'content_selector' => '.item-excerpt',
            'date_selector' => '.item-post-date',
            'link_selector' => '.post-result-item',
            'is_active' => true,
        ]);

        Company::firstOrCreate([
            'name' => 'SEALSQ Corp',
            'url' => 'https://www.sealsq.com',
            'blog_url' => 'https://www.sealsq.com',
            'favicon_url' => 'https://media.licdn.com/dms/image/v2/C4D0BAQEkOsb9f-tNIQ/company-logo_200_200/company-logo_200_200/0/1679499674670/sealsq_logo?e=2147483647&v=beta&t=_NI1pHsH-WjROzSs_gVeIaOa2f23ji-41jLyiqkht8o',
            'ticker' => 'LAES',
            'title_selector' => 'h1 span',
            'content_selector' => '.blog-post__body span',
            'date_selector' => 'time, .date, meta[property="article:published_time"]',
            'link_selector' => '.buttons-container a',
            'is_active' => true,
        ]);

        Company::firstOrCreate([
            'name' => 'Advanced Micro Devices',
            'url' => 'https://www.amd.com',
            'blog_url' => 'https://www.amd.com/en/newsroom.html',
            'favicon_url' => 'https://cdn.freebiesupply.com/logos/large/2x/amd-4-logo-png-transparent.png',
            'ticker' => 'AMD',
            'title_selector' => 'h1',
            'content_selector' => '.cmp-container__content',
            'date_selector' => '.card-date',
            'link_selector' => '.related-content-card a',
            'is_active' => true,
        ]);

        Company::firstOrCreate([
            'name' => 'Cipher Mining Inc',
            'url' => 'https://investors.ciphermining.com',
            'blog_url' => 'https://investors.ciphermining.com/news-events/press-releases',
            'favicon_url' => 'https://lh-prod-mid-pub-newsf10.s3.amazonaws.com/logo/CIFR.png',
            'ticker' => 'CIFR',
            'title_selector' => 'article > h2 .field__item',
            'content_selector' => '.node__content',
            'date_selector' => '.ndq-date .field__item',
            'link_selector' => '.nir-widget--list > article',
            'is_active' => true,
        ]);

        Company::firstOrCreate([
            'name' => 'Standard Lithium Ltd',
            'url' => 'https://www.standardlithium.com',
            'blog_url' => 'https://www.standardlithium.com/investors/news-events/press-releases',
            'favicon_url' => 'https://media.licdn.com/dms/image/v2/C560BAQFEgzHKNWogaQ/company-logo_200_200/company-logo_200_200/0/1657831113337/standard_lithium_logo?e=2147483647&v=beta&t=cwD0MuKb3grxt6N3_y_9X9bqa91itMr10_rPHIjFy80',
            'ticker' => 'SLI',
            'title_selector' => 'h1.article-heading',
            'content_selector' => '.full-news-article',
            'date_selector' => '.related-documents-line > time',
            'link_selector' => '.content > .media',
            'is_active' => true,
        ]);

        Company::firstOrCreate([
            'name' => 'Rekor Systems Inc',
            'url' => 'https://www.rekor.ai',
            'blog_url' => 'https://www.rekor.ai/blog',
            'favicon_url' => 'https://companieslogo.com/img/orig/REKR-0fc5c127.png?t=1720244493',
            'ticker' => 'REKR',
            'title_selector' => 'h3.blog-title',
            'content_selector' => 'p.text-size-small',
            'date_selector' => 'div.text-size-small',
            'link_selector' => 'a.blog-item',
            'is_active' => true,
        ]);

        Company::firstOrCreate([
            'name' => 'Nuvve Holding Corp',
            'url' => 'https://investors.nuvve.com',
            'blog_url' => 'https://investors.nuvve.com',
            'favicon_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSx6fr02xwq45Xsb6Ww8-3UGFWG2wQK46265A&s',
            'ticker' => 'NVVE',
            'title_selector' => '.nir-widget--news--headline a',
            'content_selector' => '.nir-widget--news--teaser',
            'date_selector' => '.ndq-press-date',
            'link_selector' => '.nir-widget--news--headline a',
            'is_active' => true,
        ]);
    }
}
