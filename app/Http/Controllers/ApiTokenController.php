<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * API token management for the signed-in account.
 *
 * The plain token is returned exactly once — only its SHA-256 hash is stored.
 */
class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $tokens = ApiToken::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get(['id', 'name', 'prefix', 'abilities', 'last_used_at', 'expires_at', 'revoked_at', 'created_at']);

        return response()->json(['data' => $tokens]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', Rule::in([ApiToken::ABILITY_ALL, ...ApiToken::ABILITIES])],
        ]);

        $expiresAt = isset($validated['expires_in_days'])
            ? now()->addDays((int) $validated['expires_in_days'])
            : null;

        // Omitting `abilities` preserves the legacy unrestricted token used by
        // first-party clients; supplying it creates a least-privilege token.
        [$token, $plain] = ApiToken::issue(
            $user,
            $validated['name'],
            $validated['abilities'] ?? null,
            $expiresAt,
        );

        return response()->json([
            'message' => 'API token created. Copy it now — it will not be shown again.',
            'token' => $plain,
            'id' => $token->id,
            'prefix' => $token->prefix,
            'abilities' => $token->abilities,
            'expires_at' => $token->expires_at?->toIso8601String(),
        ], 201);
    }

    public function destroy(Request $request, int $id)
    {
        $user = Auth::user();

        $token = ApiToken::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $token->revoke();

        return response()->json(['message' => 'API token revoked.']);
    }
}
