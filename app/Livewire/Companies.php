<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Companies extends Component
{
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

    public function render()
    {
        $companies = Company::where('is_active', true)
            ->withCount('posts')
            ->with('stockPrices')
            ->get()
            ->map(function ($company) {
                $company->is_followed = $company->isFollowedBy(Auth::user());
                $company->latest_stock_price = $company->getLatestStockPrice();
                $company->latest_blog_score = $company->getLatestBlogScoreWithStatus();
                $company->last_post_at = $company->posts()->latest('published_at')->first()?->published_at;
                return $company;
            })
            ->sortByDesc('last_post_at');

        return view('livewire.companies', [
            'companies' => $companies,
        ]);
    }
}
