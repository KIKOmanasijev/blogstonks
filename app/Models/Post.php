<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'user_notified_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
