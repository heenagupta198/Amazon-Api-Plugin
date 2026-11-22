<?php

namespace App\Services;

use App\Models\IndexSnapshot;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MarketFeedService
{
    public function __construct(
        private readonly DhanApiService $dhan,
        private readonly ContributionService $contribution
    ) {}

    public function refreshQuotes(): array
    {
        $stocks = Stock::query()
            ->where('is_active', true)
            ->whereNotNull('security_id')
            ->get();

        if ($stocks->isEmpty()) {
            return ['updated' => 0, 'message' => 'No stocks with security_id. Run import first.'];
        }

        if (! $this->dhan->isConfigured()) {
            return ['updated' => 0, 'message' => 'Dhan credentials missing; using stored values only.'];
        }

        $ids = $stocks->pluck('security_id')->map(fn ($id) => (int) $id)->all();
        $response = $this->dhan->marketOhlc($ids, 'NSE_EQ');
        $segmentData = $response['data']['NSE_EQ'] ?? [];

        $niftyQuote = $this->fetchNiftyQuote();
        $indexRef = $niftyQuote['previous_close'] ?? $niftyQuote['ltp'] ?? 26000;

        $updated = 0;
        foreach ($stocks as $stock) {
            $key = (string) $stock->security_id;
            if (! isset($segmentData[$key])) {
                continue;
            }

            $quote = $segmentData[$key];
            $ltp = (float) ($quote['last_price'] ?? 0);
            $prevClose = (float) ($quote['ohlc']['close'] ?? $stock->previous_close ?? $ltp);

            if ($prevClose <= 0) {
                continue;
            }

            $changeAmount = round($ltp - $prevClose, 4);
            $changePercent = round(($changeAmount / $prevClose) * 100, 4);

            $stock->ltp = $ltp;
            $stock->previous_close = $prevClose;
            $stock->change_amount = $changeAmount;
            $stock->change_percent = $changePercent;
            $stock->quotes_updated_at = now();
            $this->contribution->applyToStock($stock, (float) $indexRef);
            $stock->save();
            $updated++;
        }

        $summary = $this->contribution->summarize(
            Stock::query()->where('is_active', true)->get()
        );

        IndexSnapshot::query()->updateOrCreate(
            ['trade_date' => Carbon::today()->toDateString()],
            [
                'nifty_value' => $niftyQuote['ltp'] ?? null,
                'nifty_previous_close' => $niftyQuote['previous_close'] ?? null,
                'nifty_change' => isset($niftyQuote['ltp'], $niftyQuote['previous_close'])
                    ? round($niftyQuote['ltp'] - $niftyQuote['previous_close'], 2)
                    : null,
                'total_positive' => $summary['total_positive'],
                'total_negative' => $summary['total_negative'],
                'net_contribution' => $summary['net_contribution'],
                'advancers' => $summary['advancers'],
                'decliners' => $summary['decliners'],
                'unchanged' => $summary['unchanged'],
                'is_live' => $this->dhan->isConfigured(),
            ]
        );

        return [
            'updated' => $updated,
            'index_reference' => $indexRef,
            'nifty' => $niftyQuote,
            'summary' => $summary,
        ];
    }

    /**
     * @return array{ltp: ?float, previous_close: ?float}
     */
    public function fetchNiftyQuote(): array
    {
        if (! $this->dhan->isConfigured()) {
            return ['ltp' => null, 'previous_close' => null];
        }

        $segment = (string) config('dhan.nifty_exchange_segment');
        $securityId = (int) config('dhan.nifty_index_security_id');

        try {
            $response = $this->dhan->marketOhlcBySegment([
                $segment => [$securityId],
            ]);
            $node = $response['data'][$segment][(string) $securityId] ?? null;
            if (! $node) {
                return ['ltp' => null, 'previous_close' => null];
            }

            return [
                'ltp' => isset($node['last_price']) ? (float) $node['last_price'] : null,
                'previous_close' => isset($node['ohlc']['close']) ? (float) $node['ohlc']['close'] : null,
            ];
        } catch (\Throwable) {
            return ['ltp' => null, 'previous_close' => null];
        }
    }

    public function dashboardPayload(): array
    {
        $snapshot = IndexSnapshot::query()->latest('id')->first();
        /** @var Collection<int, Stock> $stocks */
        $stocks = Stock::query()
            ->where('is_active', true)
            ->orderByDesc('net_contribution')
            ->get();

        $positive = $stocks->filter(fn (Stock $s) => (float) $s->net_contribution > 0)
            ->sortByDesc('net_contribution')
            ->take(10)
            ->values();

        $negative = $stocks->filter(fn (Stock $s) => (float) $s->net_contribution < 0)
            ->sortBy('net_contribution')
            ->take(10)
            ->values();

        $summary = $snapshot ? [
            'total_positive' => (float) $snapshot->total_positive,
            'total_negative' => (float) $snapshot->total_negative,
            'net_contribution' => (float) $snapshot->net_contribution,
            'advancers' => $snapshot->advancers,
            'decliners' => $snapshot->decliners,
            'unchanged' => $snapshot->unchanged,
            'nifty_value' => $snapshot->nifty_value !== null ? (float) $snapshot->nifty_value : null,
            'nifty_change' => $snapshot->nifty_change !== null ? (float) $snapshot->nifty_change : null,
            'is_live' => (bool) $snapshot->is_live,
            'as_of' => optional($stocks->max('quotes_updated_at'))?->toIso8601String(),
        ] : $this->demoSummary($stocks);

        return [
            'title' => 'NIFTY 50 – LIVE ANALYSIS',
            'summary' => $summary,
            'top_positive' => $positive->map(fn (Stock $s) => $this->stockRow($s))->all(),
            'top_negative' => $negative->map(fn (Stock $s) => $this->stockRow($s))->all(),
            'dhan_configured' => $this->dhan->isConfigured(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stockRow(Stock $stock): array
    {
        return [
            'symbol' => $stock->symbol,
            'name' => $stock->name,
            'points' => (float) $stock->net_contribution,
            'change_percent' => $stock->change_percent !== null ? (float) $stock->change_percent : null,
            'weightage' => $stock->weightage !== null ? (float) $stock->weightage : null,
            'ltp' => $stock->ltp !== null ? (float) $stock->ltp : null,
        ];
    }

    /**
     * @param  Collection<int, Stock>  $stocks
     * @return array<string, mixed>
     */
    private function demoSummary(Collection $stocks): array
    {
        if ($stocks->isEmpty()) {
            return [
                'total_positive' => 0,
                'total_negative' => 0,
                'net_contribution' => 0,
                'advancers' => 0,
                'decliners' => 0,
                'unchanged' => 0,
                'nifty_value' => null,
                'nifty_change' => null,
                'is_live' => false,
                'as_of' => null,
            ];
        }

        $calc = $this->contribution->summarize($stocks);

        return array_merge($calc, [
            'nifty_value' => 25950.0,
            'nifty_change' => $calc['net_contribution'],
            'is_live' => false,
            'as_of' => null,
        ]);
    }
}