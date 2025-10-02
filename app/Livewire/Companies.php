<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Companies extends Component
{
    use WithPagination;

    public $isLoading = false;

    public function toggleFollow($companyId)
    {
        $company = Company::findOrFail($companyId);
        $user = Auth::user();
        
        if ($company->isFollowedBy($user)) {
            $user->followedCompanies()->detach($companyId);
            session()->flash('message', "Unfollowed {$company->name}");
        } else {
            $user->followedCompanies()->attach($companyId);
            session()->flash('message', "Now following {$company->name}");
        }
    }

    public function nextPage()
    {
        $this->isLoading = true;
        $this->setPage($this->getPage() + 1);
    }

    public function previousPage()
    {
        $this->isLoading = true;
        $this->setPage($this->getPage() - 1);
    }

    public function gotoPage($page)
    {
        $this->isLoading = true;
        $this->setPage($page);
    }

    public function render()
    {
        // Reset loading state after render
        $this->isLoading = false;
        
        $user = Auth::user();
        
        // Use optimized queries to avoid N+1 problems
        $companies = Company::where('is_active', true)
            ->withCount('posts')
            ->with([
                'stockPrices' => function($query) {
                    $query->latest('price_at')->limit(1);
                },
                'posts' => function($query) {
                    $query->latest('published_at')->limit(1);
                }
            ])
            ->paginate(9);

        // Add additional data to each company
        $companies->getCollection()->transform(function ($company) use ($user) {
            $company->is_followed = $company->isFollowedBy($user);
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
        });

        return view('livewire.companies', [
            'companies' => $companies,
        ]);
    }
}
