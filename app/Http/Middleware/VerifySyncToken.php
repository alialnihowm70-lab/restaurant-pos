<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySyncToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $serverToken = env('SYNC_TOKEN');

        // If a sync token is configured on the server, verify it
        if (!empty($serverToken)) {
            $clientToken = $request->header('X-Sync-Token') ?: $request->input('sync_token');
            
            if (!$clientToken || $clientToken !== $serverToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Invalid or missing X-Sync-Token header.'
                ], 401);
            }
        }

        return $next($request);
    }
}
