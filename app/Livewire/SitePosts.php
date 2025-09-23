<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\UserCompanyView;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SitePosts extends Component
{
    public Company $company;
    public $posts;

    public function mount(Company $company)
    {
        $this->company = $company;
        $this->loadPosts();
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
