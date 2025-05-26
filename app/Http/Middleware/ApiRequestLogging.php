<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class ApiRequestLogging
{
    public function handle($request, Closure $next)
    {
        // Log the request
        Log::info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
            'user' => $request->user() ? $request->user()->id : 'unauthenticated'
        ]);

        $response = $next($request);

        // Log the response
        Log::info('API Response', [
            'status' => $response->status(),
            'content' => $response->content()
        ]);

        return $response;
    }
}