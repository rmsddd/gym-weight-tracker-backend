<?php

namespace App\Http\Middleware;

use App\Support\PermissionCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The admin page and its API are the root account's alone. */
class EnsureUserIsRoot
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole(PermissionCatalog::ROOT)) {
            return response()->json(['message' => 'Only root can do this.'], 403);
        }

        return $next($request);
    }
}
