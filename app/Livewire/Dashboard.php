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

    public function unfollowCompany($companyId)
    {
        $user = Auth::user();
        $company = Company::findOrFail($companyId);
        
        $user->followedCompanies()->detach($companyId);
        
        $this->dispatch('company-unfollowed', companyId: $companyId);
    }

    public function render()
    {
        $user = Auth::user();
        
        // Get companies with optimized queries to avoid N+1 problems
        $companies = $user->followedCompanies()
            ->where('is_active', true)
            ->withCount('posts')
            ->with([
                'stockPrices' => function($query) {
                    $query->latest('price_at')->limit(1);
                },
                'posts' => function($query) {
                    $query->latest('published_at')->limit(1);
                }
            ])
            ->get()
            ->map(function ($company) use ($user) {
                $company->new_posts_count = $company->getNewPostsCountForUser($user);
                $company->latest_stock_price = $company->stockPrices->first();
                
                // Get latest blog score from already loaded post
                $latestPost = $company->posts->first();
                if ($latestPost && $latestPost->importance_score !== null) {
                    $isHuge = $latestPost->is_huge_news && $latestPost->published_at->diffInHours(now()) <= 12;
                    $company->latest_blog_score = [
                        'score' => $latestPost->importance_score,
                        'is_huge' => $isHuge,
                        'scored_at' => $latestPost->scored_at,
                        'post_title' => $latestPost->title,
                    ];
                } else {
                    $company->latest_blog_score = null;
                }
                
                $company->last_post_at = $latestPost?->published_at;
                return $company;
            })
            ->sortByDesc('last_post_at');

        return view('livewire.dashboard', [
            'companies' => $companies,
        ]);
    }
}
