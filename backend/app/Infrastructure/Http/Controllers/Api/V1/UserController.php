<?php

namespace App\Infrastructure\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController
{
    public function index(Request $request): JsonResponse
    {
        $currentUserId = $request->user()->id;

        $users = User::where('id', '!=', $currentUserId)
            ->with(['wallets' => fn ($q) => $q->where('currency', 'BRL')->limit(1)])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id'          => $user->id,
                'name'        => $user->name,
                'wallet_uuid' => $user->wallets->first()?->uuid,
            ])
            ->filter(fn ($u) => $u['wallet_uuid'] !== null)
            ->values();

        return response()->json([
            'success' => true,
            'data'    => ['users' => $users],
        ]);
    }
}
