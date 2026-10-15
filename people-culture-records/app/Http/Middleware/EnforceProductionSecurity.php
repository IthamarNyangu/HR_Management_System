<?php

namespace App\Http\Middleware;

use App\Support\ProductionSecurity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnforceProductionSecurity
{
    public function __construct(private readonly ProductionSecurity $security) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && ($failures = $this->security->failures()) !== []) {
            Log::critical('Production request blocked because security configuration is incomplete.', ['failures' => $failures]);

            return response('This service is temporarily unavailable while its security configuration is completed.', 503);
        }

        return $next($request);
    }
}
