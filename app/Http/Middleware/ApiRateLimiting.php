<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiting
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        // Define limits for different endpoints
        $limits = [
            'password/*' => ['tries' => 5, 'minutes' => 60], // Password operations
            'profile' => ['tries' => 60, 'minutes' => 60],   // Profile operations
            'default' => ['tries' => 60, 'minutes' => 1],    // Default limit
        ];

        // Get the appropriate limit for this route
        $limit = $this->getRouteLimit($request, $limits);

        if ($this->limiter->tooManyAttempts($key, $limit['tries'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many attempts. Please try again later.',
                'retry_after' => $this->limiter->availableIn($key)
            ], 429);
        }

        $this->limiter->hit($key, $limit['minutes'] * 60);

        $response = $next($request);

        return $response->header('X-RateLimit-Limit', $limit['tries'])
                       ->header('X-RateLimit-Remaining', $this->limiter->retriesLeft($key, $limit['tries']));
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(implode('|', [
            $request->method(),
            $request->path(),
            $request->ip(),
            $request->userAgent()
        ]));
    }

    protected function getRouteLimit(Request $request, array $limits): array
    {
        $path = $request->path();
        
        foreach ($limits as $pattern => $limit) {
            if ($pattern === 'default') continue;
            if (Str::is($pattern, $path)) {
                return $limit;
            }
        }
        
        return $limits['default'];
    }
}