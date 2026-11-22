<?php

namespace App\Services;

use App\Models\Stock;

class ContributionService
{
    /**
     * Approximate index point contribution:
     * previous_index_value × (weightage/100) × (change_percent/100)
     */
    public function calculatePointContribution(
        float $indexReferenceValue,
        float $weightagePercent,
        float $changePercent
    ): float {
        return round(
            $indexReferenceValue * ($weightagePercent / 100) * ($changePercent / 100),
            2
        );
    }

  public function applyToStock(Stock $stock, float $indexReferenceValue): Stock
    {
        $weight = (float) ($stock->weightage ?? 0);
        $changePercent = (float) ($stock->change_percent ?? 0);

        $points = $this->calculatePointContribution($indexReferenceValue, $weight, $changePercent);

        $stock->positive_points = $points > 0 ? abs($points) : 0;
        $stock->negative_points = $points < 0 ? abs($points) : 0;
        $stock->net_contribution = $points;

        return $stock;
    }

    /**
     * @param  iterable<Stock>  $stocks
     * @return array{
     *   total_positive: float,
     *   total_negative: float,
     *   net_contribution: float,
     *   advancers: int,
     *   decliners: int,
     *   unchanged: int
     * }
     */
    public function summarize(iterable $stocks): array
    {
        $totalPositive = 0.0;
        $totalNegative = 0.0;
        $advancers = 0;
        $decliners = 0;
        $unchanged = 0;

        foreach ($stocks as $stock) {
            $totalPositive += (float) $stock->positive_points;
            $totalNegative += (float) $stock->negative_points;

            $change = (float) ($stock->change_percent ?? 0);
            if ($change > 0) {
                $advancers++;
            } elseif ($change < 0) {
                $decliners++;
            } else {
                $unchanged++;
            }
        }

        return [
            'total_positive' => round($totalPositive, 2),
            'total_negative' => round(-$totalNegative, 2),
            'net_contribution' => round($totalPositive - $totalNegative, 2),
            'advancers' => $advancers,
            'decliners' => $decliners,
            'unchanged' => $unchanged,
        ];
    }
}