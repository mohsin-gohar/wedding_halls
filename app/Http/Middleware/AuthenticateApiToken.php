<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Support\TenantAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate API callers.
 *
 * Two principals are accepted:
 *   1. A stateless bearer token (mobile app / partner integration) — the
 *      token is hashed at rest and must be active, unrevoked and unexpired.
 *   2. An existing browser session, so the ERP and partner dashboards can
 *      call the same endpoints without a second token.
 *
 * If neither is present the request is rejected with 401 — the API never
 * falls through to an unscoped query.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $token = ApiToken::resolveWithToken($request->bearerToken());
        $user = $token?->user ?? $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated. A valid bearer token is required.',
            ], 401);
        }

        if (in_array($user->status, ['SUSPENDED', 'INACTIVE', 'BLOCKED'], true)) {
            return response()->json([
                'message' => 'This account is not active.',
            ], 403);
        }

        if (! $user->isSuperAdmin() && TenantAccess::isLocked($user->tenant)) {
            return response()->json([
                'message' => TenantAccess::lockMessage(),
            ], 403);
        }

        // A browser session keeps its full role permissions. Only stateless
        // bearer tokens are restricted by the abilities granted at issue time.
        foreach ($abilities as $ability) {
            if ($token && ! $token->hasAbility($ability)) {
                return response()->json([
                    'message' => 'This API token is missing the required ability: '.$ability,
                ], 403);
            }
        }

        $request->attributes->set('api_token', $token);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
