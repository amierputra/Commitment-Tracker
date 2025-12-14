<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Return404OnUnauthenticated
{
    /**
     * Handle an incoming request.
     *
     * If the user is not authenticated, return a 404 response
     * to make the application appear non-existent.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            abort(404);
        }

        return $next($request);
    }
}
