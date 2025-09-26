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
        
        // Use a simpler approach with Eloquent pagination
        $companies = Company::where('is_active', true)
            ->withCount('posts')
            ->with('stockPrices')
            ->paginate(9);

        // Add additional data to each company
        $companies->getCollection()->transform(function ($company) {
            $company->is_followed = $company->isFollowedBy(Auth::user());
            $company->latest_stock_price = $company->getLatestStockPrice();
            $company->latest_blog_score = $company->getLatestBlogScoreWithStatus();
            $company->last_post_at = $company->posts()->latest('published_at')->first()?->published_at;
            return $company;
        });

        return view('livewire.companies', [
            'companies' => $companies,
        ]);
    }
}
