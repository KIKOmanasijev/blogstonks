<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\AnthropicService;

class Post extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'content',
        'url',
        'published_at',
        'external_id',
        'user_notified_at',
        'is_huge_news',
        'importance_score',
        'scored_at',
        'reasoning',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'user_notified_at' => 'datetime',
        'is_huge_news' => 'boolean',
        'scored_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function classify(bool $force = false): bool
    {
        // Skip if already classified and not forcing
        if (!$force && $this->isClassified()) {
            return true; // Already classified, consider it successful
        }

        $anthropicService = app(AnthropicService::class);
        
        // Get a truncated description from content (first 500 characters)
        $description = $this->content ? substr(strip_tags($this->content), 0, 500) : null;
        
        $classification = $anthropicService->classifyPost($this->title, $description);
        
        if ($classification) {
            $this->update([
                'is_huge_news' => $classification['huge'],
                'importance_score' => $classification['score'],
                'scored_at' => now(),
                'reasoning' => $classification['reasoning'] ?? null,
            ]);
            
            // Send Telegram notification if score is >= 75
            if ($classification['score'] >= 75) {
                $telegramService = app(\App\Services\TelegramNotificationService::class);
                $telegramService->sendHighScorePostNotification($this);
            }
            
            return true;
        }
        
        return false;
    }

    public function isClassified(): bool
    {
        return !is_null($this->is_huge_news) && !is_null($this->importance_score) && !is_null($this->scored_at);
    }

    public function isHugeNews(): bool
    {
        return $this->is_huge_news === true;
    }

    public function getImportanceScore(): ?int
    {
        return $this->importance_score;
    }
}
