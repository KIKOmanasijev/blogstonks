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
    }
}
