<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Friends find each other by username, so an account without one cannot take part yet. */
class EnsureUserHasUsername
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->username === null) {
            return response()->json(['message' => 'Choose a username first.'], 403);
        }

        return $next($request);
    }
}
