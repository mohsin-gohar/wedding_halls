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

        // New tokens are least-privilege and time-limited by default. A caller
        // must explicitly request the wildcard ability to receive unrestricted
        // access. Tokens issued before this change keep their stored `null`
        // abilities for backward compatibility, but they are never created
        // through this endpoint again.
        $abilities = $validated['abilities'] ?? [
            'bookings.read',
            'service-bookings.read',
            'payments.read',
        ];

        // Wildcard tokens are a platform-administrator capability. Tenant and
        // customer accounts may only mint explicitly scoped, least-privilege
        // tokens; downstream RBAC still applies to every token.
        if (in_array(ApiToken::ABILITY_ALL, $abilities, true) && ! $user->isSuperAdmin()) {
            abort(403, 'Only platform administrators may issue unrestricted API tokens.');
        }

        $expiresAt = now()->addDays((int) ($validated['expires_in_days'] ?? 90));

        [$token, $plain] = ApiToken::issue(
            $user,
            $validated['name'],
            $abilities,
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
