<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'isin',
        'industry',
        'security_id',
        'weightage',
        'previous_close',
        'ltp',
        'change_amount',
        'change_percent',
        'volume',
        'positive_points',
        'negative_points',
        'net_contribution',
        'quotes_updated_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weightage' => 'decimal:4',
            'previous_close' => 'decimal:4',
            'ltp' => 'decimal:4',
            'change_amount' => 'decimal:4',
            'change_percent' => 'decimal:4',
            'positive_points' => 'decimal:4',
            'negative_points' => 'decimal:4',
            'net_contribution' => 'decimal:4',
            'quotes_updated_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}