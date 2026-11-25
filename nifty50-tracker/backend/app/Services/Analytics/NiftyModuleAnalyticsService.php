<?php

namespace App\Services\Analytics;

use App\Models\ModuleSnapshot;
use App\Models\Stock;
use App\Services\DhanApiService;
use App\Services\DhanHistoricalService;
use App\Services\MarketFeedService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NiftyModuleAnalyticsService
{
    private const TIMEFRAMES = [
        '5m' => '5',
        '15m' => '15',
        '1h' => '60',
        '1d' => '60', // intraday hour bucket as proxy until EOD daily candle refresh
    ];

    public function __construct(
        private readonly DhanApiService $dhan,
        private readonly DhanHistoricalService $historical,
        private readonly MarketFeedService $market
    ) {}

    public function getPayload(): array
    {
        $cached = ModuleSnapshot::query()->where('module_key', 'nifty50_modules')->first();
        if ($cached && $cached->computed_at?->gt(now()->subMinutes(2))) {
            return $cached->payload ?? [];
        }

        return $this->buildPayload();
    }

    public function refreshAndStore(): array
    {
        $payload = $this->buildPayload(true);

        ModuleSnapshot::query()->updateOrCreate(
            ['module_key' => 'nifty50_modules'],
            ['payload' => $payload, 'computed_at' => now()]
        );

        return $payload;
    }

    private function buildPayload(bool $deep = false): array
    {
        $dashboard = $this->market->dashboardPayload();
        /** @var Collection<int, Stock> $stocks */
        $stocks = Stock::query()->where('is_active', true)->get();

        $unusual = $stocks
            ->map(function (Stock $s) use ($deep) {
                if ($deep && $this->dhan->isConfigured() && $s->security_id) {
                    $avg = $this->historical->avgDailyVolume20d((int) $s->security_id);
                    if ($avg) {
                        $s->avg_volume_20d = $avg;
                        $vol = (int) ($s->volume ?? 0);
                        $s->unusual_volume_ratio = $avg > 0 ? round($vol / $avg, 4) : null;
                        $s->save();
                    }
                }

                $ratio = (float) ($s->unusual_volume_ratio ?? 0);
                $vol = (int) ($s->volume ?? 0);
                $avg = (int) ($s->avg_volume_20d ?? 0);
                if ($ratio <= 0 && $avg > 0 && $vol > 0) {
                    $ratio = $vol / $avg;
                }

                return [
                    'symbol' => $s->symbol,
                    'name' => $s->name,
                    'volume' => $vol,
                    'avg_volume_20d' => $avg ?: null,
                    'ratio' => $ratio > 0 ? round($ratio, 2) : null,
                    'unusual' => $ratio >= 1.5,
                    'points' => (float) $s->net_contribution,
                ];
            })
            ->filter(fn (array $row) => $row['unusual'])
            ->sortByDesc('ratio')
            ->values()
            ->take(10)
            ->all();

        $vwapAbove = [];
        $superTrend = [];
        foreach (array_keys(self::TIMEFRAMES) as $tf) {
            $vwapAbove[$tf] = [];
            $superTrend[$tf] = [];
        }

        if ($deep && $this->dhan->isConfigured()) {
            $candidates = $stocks->sortByDesc('volume')->take(12)->values();
            foreach ($candidates as $stock) {
                if (! $stock->security_id) {
                    continue;
                }
                foreach (self::TIMEFRAMES as $label => $interval) {
                    try {
                        $candles = $this->historical->intraday(
                            (int) $stock->security_id,
                            $interval,
                            Carbon::now('Asia/Kolkata')->startOfDay(),
                            Carbon::now('Asia/Kolkata'),
                            false
                        );
                        $vwap = CandleMath::vwap($candles);
                        $ltp = (float) ($stock->ltp ?? 0);
                        if ($vwap && $ltp > $vwap) {
                            $vwapAbove[$label][] = [
                                'symbol' => $stock->symbol,
                                'name' => $stock->name,
                                'ltp' => $ltp,
                                'vwap' => $vwap,
                                'distance_pct' => round((($ltp - $vwap) / $vwap) * 100, 2),
                            ];
                        }
                        $signal = CandleMath::superTrendSignal($candles);
                        if ($signal === 1) {
                            $superTrend[$label][] = [
                                'symbol' => $stock->symbol,
                                'name' => $stock->name,
                                'signal' => 'bullish_breakout',
                            ];
                        } elseif ($signal === -1) {
                            $superTrend[$label][] = [
                                'symbol' => $stock->symbol,
                                'name' => $stock->name,
                                'signal' => 'bearish_breakdown',
                            ];
                        }
                    } catch (\Throwable) {
                        continue;
                    }
                    usleep(1_100_000); // respect ~1 req/s
                }
            }
            foreach ($vwapAbove as $k => $rows) {
                usort($rows, fn ($a, $b) => ($b['distance_pct'] ?? 0) <=> ($a['distance_pct'] ?? 0));
                $vwapAbove[$k] = array_slice($rows, 0, 5);
            }
            foreach ($superTrend as $k => $rows) {
                $superTrend[$k] = array_slice($rows, 0, 5);
            }
        }

        $oiRows = $stocks
            ->map(fn (Stock $s) => [
                'symbol' => $s->symbol,
                'name' => $s->name,
                'oi_change' => null,
                'oi_change_pct' => null,
                'note' => 'Map NSE_FNO contract in Phase-2 for live OI',
            ])
            ->take(5)
            ->all();

        return [
            'brand' => config('traderdomina.name'),
            'product' => 'NiftyPulse',
            'scope' => 'Nifty 50 only',
            'dhan_ready' => $this->dhan->isConfigured(),
            'compliance_note' => 'Paid subscriber display may require Dhan FinTech integration — keep app login-gated until approved.',
            'top5' => [
                'positive' => $dashboard['top_positive'] ?? [],
                'negative' => $dashboard['top_negative'] ?? [],
                'volume' => $dashboard['top_volume'] ?? [],
            ],
            'unusual_volume' => $unusual,
            'vwap_above' => $vwapAbove,
            'supertrend' => $superTrend,
            'open_interest' => $oiRows,
            'computed_at' => now()->toIso8601String(),
        ];
    }
}