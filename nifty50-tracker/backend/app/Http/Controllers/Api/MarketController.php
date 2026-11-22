<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Services\MarketFeedService;
use Illuminate\Http\JsonResponse;

class MarketController extends Controller
{
    public function dashboard(MarketFeedService $market): JsonResponse
    {
        return response()->json($market->dashboardPayload());
    }

    public function stocks(): JsonResponse
    {
        $rows = Stock::query()
            ->where('is_active', true)
            ->orderBy('symbol')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function gainers(): JsonResponse
    {
        $rows = Stock::query()
            ->where('is_active', true)
            ->where('net_contribution', '>', 0)
            ->orderByDesc('net_contribution')
            ->limit(10)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function losers(): JsonResponse
    {
        $rows = Stock::query()
            ->where('is_active', true)
            ->where('net_contribution', '<', 0)
            ->orderBy('net_contribution')
            ->limit(10)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function breadth(MarketFeedService $market): JsonResponse
    {
        $payload = $market->dashboardPayload();

        return response()->json([
            'summary' => $payload['summary'],
            'top_positive' => $payload['top_positive'],
            'top_negative' => $payload['top_negative'],
        ]);
    }

    public function refresh(MarketFeedService $market): JsonResponse
    {
        $result = $market->refreshQuotes();

        return response()->json(array_merge($result, [
            'dashboard' => $market->dashboardPayload(),
        ]));
    }
}