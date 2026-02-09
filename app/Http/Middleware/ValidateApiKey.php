<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');

        // Check if API key is provided
        if (! $apiKey) {
            return response()->json([
                'message' => 'API Key is required.',
                'error' => 'Missing X-API-KEY header'
            ], 401);
        }

        // Validate API key
        if (! $this->isValidApiKey($apiKey)) {
            return response()->json([
                'message' => 'Invalid API Key.',
                'error' => 'The provided API key is not authorized'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if the provided API key is valid
     */
    private function isValidApiKey(string $key): bool
    {
        $validKeys = config('api.allowed_keys', []);

        return in_array($key, $validKeys, true);
    }
}
