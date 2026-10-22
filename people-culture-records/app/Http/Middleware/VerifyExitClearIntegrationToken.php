<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyExitClearIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.exitclear.integration_token');
        $providedToken = (string) $request->bearerToken();

        if ($configuredToken === '' || $providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json(['message' => 'Unauthorised.'], 401);
        }

        return $next($request);
    }
}
