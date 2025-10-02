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
        // Optimize to avoid loading all companies into memory
        $companyIds = Company::where('is_active', true)->pluck('id');
        
        foreach ($companyIds as $companyId) {
            UserCompanyView::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'company_id' => $companyId,
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
        
        // Ultra-minimal approach to isolate memory issue
        $companies = $user->followedCompanies()
            ->where('is_active', true)
            ->select(['id', 'name', 'ticker', 'favicon_url']) // Only select needed fields
            ->limit(10) // Limit to 10 companies max
            ->get()
            ->map(function ($company) {
                $company->new_posts_count = 0;
                $company->latest_stock_price = null;
                $company->latest_blog_score = null;
                $company->last_post_at = null;
                return $company;
            });

        return view('livewire.dashboard', [
            'companies' => $companies,
        ]);
    }
}
