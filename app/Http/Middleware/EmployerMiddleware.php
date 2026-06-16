<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isEmployer()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Employer access required.'
            ], 403);
        }

        return $next($request);
    }
}
