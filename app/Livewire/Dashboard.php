<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\UserCompanyView;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function markAllAsRead()
    {
        $companies = Company::where('is_active', true)->get();
        
        foreach ($companies as $company) {
            UserCompanyView::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'company_id' => $company->id,
                ],
                [
                    'last_viewed_at' => now(),
                ]
            );
        }
        
        $this->dispatch('all-marked-as-read');
    }

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
                $company->last_post_at = $company->posts()->latest('published_at')->first()?->published_at;
                return $company;
            })
            ->sortByDesc('last_post_at');

        return view('livewire.dashboard', [
            'companies' => $companies,
        ]);
    }
}
