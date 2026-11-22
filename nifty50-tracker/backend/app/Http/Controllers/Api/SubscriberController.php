<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SubscriberController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->orderByDesc('is_admin')
            ->orderBy('email')
            ->get(['id', 'name', 'email', 'is_admin', 'is_active', 'subscription_expires_at', 'created_at']);

        return response()->json(['data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc,dns', 'max:190', 'unique:users,email'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
            'subscription_days' => ['required', 'integer', 'min:1', 'max:730'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_admin' => false,
            'is_active' => true,
            'subscription_expires_at' => now()->addDays((int) $data['subscription_days']),
        ]);

        return response()->json(['user' => $user], 201);
    }

    public function extend(Request $request, User $user): JsonResponse
    {
        if ($user->is_admin) {
            return response()->json(['message' => 'Admin accounts do not use subscription expiry.'], 422);
        }

        $data = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:730'],
        ]);

        $base = $user->subscription_expires_at && $user->subscription_expires_at->isFuture()
            ? $user->subscription_expires_at
            : now();

        $user->subscription_expires_at = $base->copy()->addDays((int) $data['days']);
        $user->is_active = true;
        $user->save();

        return response()->json(['user' => $user]);
    }

    public function deactivate(User $user): JsonResponse
    {
        if ($user->is_admin) {
            return response()->json(['message' => 'Cannot deactivate admin from API.'], 422);
        }

        $user->is_active = false;
        $user->tokens()->delete();
        $user->save();

        return response()->json(['message' => 'Subscriber deactivated']);
    }
}