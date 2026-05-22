<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordHasBeenChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('password.change', 'password.change.update', 'app.logout')) {
            return $next($request);
        }

        return redirect()->route('password.change')
            ->with('warning', 'Please change your temporary password before continuing.');
    }
}
