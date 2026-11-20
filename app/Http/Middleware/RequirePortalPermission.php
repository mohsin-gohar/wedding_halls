<?php

namespace App\Http\Middleware;

use App\Support\PermissionMatrix;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePortalPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless(
            PermissionMatrix::userCan($request->user(), $permission),
            403,
            'Unauthorized. Missing permission: '.$permission
        );

        return $next($request);
    }
}
