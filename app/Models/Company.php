<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'name',
        'url',
        'blog_url',
        'favicon_url',
        'ticker',
        'title_selector',
        'content_selector',
        'date_selector',
        'link_selector',
        'is_active',
        'last_scraped_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_scraped_at' => 'datetime',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function userViews(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_company_views')
            ->withPivot('last_viewed_at')
            ->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_company_follows')
            ->withTimestamps();
    }

    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('user_id', $user->id)->exists();
    }

    public function stockPrices(): HasMany
    {
        return $this->hasMany(StockPrice::class);
    }

    public function getLatestStockPrice(): ?StockPrice
    {
        return $this->stockPrices()->latest('price_at')->first();
    }

    public function getNewPostsCountForUser(User $user): int
    {
        $lastViewed = $this->userViews()
            ->where('user_id', $user->id)
            ->first()?->pivot?->last_viewed_at;

        if (!$lastViewed) {
            return $this->posts()->count();
        }

        return $this->posts()
            ->where('published_at', '>', $lastViewed)
            ->count();
    }

    public function getUnnotifiedPostsCount(): int
    {
        return $this->posts()
            ->whereNull('user_notified_at')
            ->count();
    }

    public function getLatestBlogScore(): ?int
    {
        $latestPost = $this->posts()
            ->whereNotNull('importance_score')
            ->latest('published_at')
            ->first();

        return $latestPost ? $latestPost->importance_score : null;
    }

    public function getLatestBlogScoreWithStatus(): ?array
    {
        $latestPost = $this->posts()
            ->whereNotNull('importance_score')
            ->latest('published_at')
            ->first();

        if (!$latestPost) {
            return null;
        }

        return [
            'score' => $latestPost->importance_score,
            'is_huge' => $latestPost->is_huge_news,
            'scored_at' => $latestPost->scored_at,
            'post_title' => $latestPost->title,
        ];
    }
}
