<?php

namespace App\Services;

use App\Models\Stock;

class WeightageService
{
    /**
     * Distributes remaining weight equally when official weight is missing.
     */
    public function fillMissingWeights(): int
    {
        $stocks = Stock::query()->where('is_active', true)->get();
        $withWeight = $stocks->filter(fn (Stock $s) => (float) $s->weightage > 0);
        $missing = $stocks->filter(fn (Stock $s) => ! (float) $s->weightage);

        if ($missing->isEmpty()) {
            return 0;
        }

        $assigned = $withWeight->sum(fn (Stock $s) => (float) $s->weightage);
        $remaining = max(0, 100 - $assigned);
        $each = $missing->count() > 0 ? round($remaining / $missing->count(), 4) : 0;

        foreach ($missing as $stock) {
            $stock->weightage = $each;
            $stock->save();
        }

        return $missing->count();
    }
}