<?php

namespace App\Services;

use Carbon\Carbon;
use RuntimeException;

class DhanHistoricalService
{
    public function __construct(
        private readonly DhanApiService $dhan
    ) {}

    /**
     * @return array{open: array<float>, high: array<float>, low: array<float>, close: array<float>, volume: array<int>, open_interest?: array<int>}
     */
    public function intraday(
        int $securityId,
        string $interval,
        ?Carbon $from = null,
        ?Carbon $to = null,
        bool $withOi = false
    ): array {
        $from ??= Carbon::now('Asia/Kolkata')->startOfDay();
        $to ??= Carbon::now('Asia/Kolkata');

        $payload = [
            'securityId' => (string) $securityId,
            'exchangeSegment' => 'NSE_EQ',
            'instrument' => 'EQUITY',
            'interval' => $interval,
            'oi' => $withOi,
            'fromDate' => $from->format('Y-m-d H:i:s'),
            'toDate' => $to->format('Y-m-d H:i:s'),
        ];

        $response = $this->dhan->post('/charts/intraday', $payload);

        return [
            'open' => $response['open'] ?? [],
            'high' => $response['high'] ?? [],
            'low' => $response['low'] ?? [],
            'close' => $response['close'] ?? [],
            'volume' => $response['volume'] ?? [],
            'open_interest' => $response['open_interest'] ?? [],
        ];
    }

    public function avgDailyVolume20d(int $securityId): ?int
    {
        $to = Carbon::today('Asia/Kolkata');
        $from = $to->copy()->subDays(30);

        $payload = [
            'securityId' => (string) $securityId,
            'exchangeSegment' => 'NSE_EQ',
            'instrument' => 'EQUITY',
            'expiryCode' => 0,
            'oi' => false,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
        ];

        try {
            $response = $this->dhan->post('/charts/historical', $payload);
        } catch (RuntimeException) {
            return null;
        }

        $volumes = $response['volume'] ?? [];
        if (count($volumes) < 5) {
            return null;
        }

        $last = array_slice($volumes, -20);

        return (int) round(array_sum($last) / count($last));
    }
}