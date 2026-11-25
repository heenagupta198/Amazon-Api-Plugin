<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\NiftyModuleAnalyticsService;
use Illuminate\Http\JsonResponse;

class ModulesController extends Controller
{
    public function nifty50(NiftyModuleAnalyticsService $modules): JsonResponse
    {
        return response()->json($modules->getPayload());
    }

    public function refresh(NiftyModuleAnalyticsService $modules): JsonResponse
    {
        return response()->json($modules->refreshAndStore());
    }
}