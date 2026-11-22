<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremiumSubscriber
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        if (! $user->hasPremiumAccess()) {
            return response()->json([
                'message' => 'Active subscription required for Yogesh Traderdomina premium access.',
                'subscription_expires_at' => $user->subscription_expires_at,
            ], 403);
        }

        return $next($request);
    }
}