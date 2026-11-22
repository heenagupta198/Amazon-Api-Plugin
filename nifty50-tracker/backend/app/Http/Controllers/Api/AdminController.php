<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\Stock;
use App\Services\InstrumentImportService;
use App\Services\WeightageService;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'stocks' => Stock::query()->where('is_active', true)->count(),
            'with_security_id' => Stock::query()->whereNotNull('security_id')->count(),
            'recent_errors' => ApiLog::query()->latest('id')->limit(10)->get(),
            'env' => [
                'dhan_configured' => filled(config('dhan.access_token')) && filled(config('dhan.client_id')),
            ],
        ]);
    }

    public function importConstituents(
        InstrumentImportService $import,
        WeightageService $weights
    ): JsonResponse {
        $result = $import->importNiftyConstituents();
        $filled = $weights->fillMissingWeights();

        return response()->json([
            'import' => $result,
            'weightage_filled' => $filled,
        ]);
    }
}