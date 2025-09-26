<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\User;
use App\Models\UserCompanyView;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SitePosts extends Component
{
    public Company $company;
    public $posts;
    public $isFollowed;

    public function mount(Company $company)
    {
        $this->company = $company;
        $this->isFollowed = $company->isFollowedBy(Auth::user());
        $this->loadPosts();
        
        // Load stock price data
        $this->company->latest_stock_price = $company->getLatestStockPrice();
        $this->company->latest_blog_score = $company->getLatestBlogScoreWithStatus();
    }

    public function toggleFollow()
    {
        $user = Auth::user();
        
        if ($this->isFollowed) {
            $user->followedCompanies()->detach($this->company->id);
            $this->isFollowed = false;
            session()->flash('message', "Unfollowed {$this->company->name}");
        } else {
            $user->followedCompanies()->attach($this->company->id);
            $this->isFollowed = true;
            session()->flash('message', "Now following {$this->company->name}");
        }
    }

    public function loadPosts()
    {
        $this->posts = $this->company->posts()
            ->orderBy('published_at', 'desc')
            ->get();
    }

    public function markAsViewed()
    {
        UserCompanyView::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'company_id' => $this->company->id,
            ],
            [
                'last_viewed_at' => now(),
            ]
        );

        $this->dispatch('posts-viewed');
    }

    public function render()
    {
        return view('livewire.site-posts');
    }
}
