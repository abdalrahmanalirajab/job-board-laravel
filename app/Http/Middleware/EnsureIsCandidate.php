<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsCandidate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isCandidate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Access denied.',
            ], 403);
        }

        return $next($request);
    }
}
