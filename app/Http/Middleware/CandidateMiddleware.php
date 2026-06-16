<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CandidateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isCandidate()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Candidate access required.'
            ], 403);
        }

        return $next($request);
    }
}
