<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleSnapshot extends Model
{
    protected $fillable = [
        'module_key',
        'payload',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'computed_at' => 'datetime',
        ];
    }
}