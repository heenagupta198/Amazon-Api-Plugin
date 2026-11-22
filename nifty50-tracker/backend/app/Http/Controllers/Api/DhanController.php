<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DhanApiService;
use Illuminate\Http\JsonResponse;

class DhanController extends Controller
{
    public function profile(DhanApiService $dhan): JsonResponse
    {
        if (! $dhan->isConfigured()) {
            return response()->json([
                'message' => 'Configure DHAN_ACCESS_TOKEN and DHAN_CLIENT_ID in .env',
            ], 422);
        }

        return response()->json($dhan->profile());
    }

    public function connectionTest(DhanApiService $dhan): JsonResponse
    {
        return response()->json($dhan->connectionTest());
    }
}