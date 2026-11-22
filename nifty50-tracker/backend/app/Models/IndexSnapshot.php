<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexSnapshot extends Model
{
    protected $fillable = [
        'trade_date',
        'nifty_value',
        'nifty_previous_close',
        'nifty_change',
        'total_volume',
        'total_positive',
        'total_negative',
        'net_contribution',
        'advancers',
        'decliners',
        'unchanged',
        'data_source',
        'is_live',
    ];

    protected function casts(): array
    {
        return [
            'trade_date' => 'date',
            'nifty_value' => 'decimal:4',
            'nifty_previous_close' => 'decimal:4',
            'nifty_change' => 'decimal:4',
            'total_positive' => 'decimal:4',
            'total_negative' => 'decimal:4',
            'net_contribution' => 'decimal:4',
            'is_live' => 'boolean',
        ];
    }
}