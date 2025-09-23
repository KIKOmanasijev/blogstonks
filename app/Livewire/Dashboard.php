<?php

namespace App\Livewire;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $companies = Company::where('is_active', true)
            ->withCount('posts')
            ->with('stockPrices')
            ->get()
            ->map(function ($company) {
                $company->new_posts_count = $company->getNewPostsCountForUser(Auth::user());
                $company->latest_stock_price = $company->getLatestStockPrice();
                $company->latest_blog_score = $company->getLatestBlogScoreWithStatus();
                return $company;
            });

        return view('livewire.dashboard', [
            'companies' => $companies,
        ]);
    }
}
