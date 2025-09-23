<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockPrice extends Model
{
    protected $fillable = [
        'company_id',
        'price',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'change',
        'change_percent',
        'price_at',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'open' => 'decimal:4',
        'high' => 'decimal:4',
        'low' => 'decimal:4',
        'close' => 'decimal:4',
        'volume' => 'integer',
        'change' => 'decimal:4',
        'change_percent' => 'decimal:4',
        'price_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
