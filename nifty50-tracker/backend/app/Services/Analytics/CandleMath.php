<?php

namespace App\Services\Analytics;

class CandleMath
{
    /**
     * @param  array{open: array<float>, high: array<float>, low: array<float>, close: array<float>, volume: array<int|float>}  $candles
     */
    public static function vwap(array $candles): ?float
    {
        $n = count($candles['close'] ?? []);
        if ($n === 0) {
            return null;
        }

        $sumPv = 0.0;
        $sumV = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $h = (float) ($candles['high'][$i] ?? 0);
            $l = (float) ($candles['low'][$i] ?? 0);
            $c = (float) ($candles['close'][$i] ?? 0);
            $v = (float) ($candles['volume'][$i] ?? 0);
            if ($v <= 0) {
                continue;
            }
            $tp = ($h + $l + $c) / 3;
            $sumPv += $tp * $v;
            $sumV += $v;
        }

        return $sumV > 0 ? round($sumPv / $sumV, 4) : null;
    }

    /**
     * SuperTrend direction on last bar: 1 bullish breakout, -1 bearish, 0 neutral.
     *
     * @param  array{high: array<float>, low: array<float>, close: array<float>}  $candles
     */
    public static function superTrendSignal(array $candles, int $period = 10, float $multiplier = 3): int
    {
        $high = $candles['high'] ?? [];
        $low = $candles['low'] ?? [];
        $close = $candles['close'] ?? [];
        $len = count($close);
        if ($len < $period + 2) {
            return 0;
        }

        $atr = self::atr($high, $low, $close, $period);
        $last = $len - 1;
        $hl2 = ((float) $high[$last] + (float) $low[$last]) / 2;
        $upper = $hl2 + $multiplier * $atr;
        $lower = $hl2 - $multiplier * $atr;
        $price = (float) $close[$last];
        $prev = (float) $close[$last - 1];

        if ($price > $upper && $price > $prev) {
            return 1;
        }
        if ($price < $lower && $price < $prev) {
            return -1;
        }

        return 0;
    }

    /**
     * @param  array<float>  $high
     * @param  array<float>  $low
     * @param  array<float>  $close
     */
    private static function atr(array $high, array $low, array $close, int $period): float
    {
        $trs = [];
        for ($i = 1, $n = count($close); $i < $n; $i++) {
            $tr = max(
                (float) $high[$i] - (float) $low[$i],
                abs((float) $high[$i] - (float) $close[$i - 1]),
                abs((float) $low[$i] - (float) $close[$i - 1])
            );
            $trs[] = $tr;
        }

        if (count($trs) < $period) {
            return array_sum($trs) / max(1, count($trs));
        }

        $slice = array_slice($trs, -$period);

        return array_sum($slice) / count($slice);
    }
}