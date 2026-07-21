<?php

namespace Plugins\TimeTracker\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $device): Response
    {
        if (!in_array($device, ['flutter', 'electron'])) {
            return response()->json(['error' => true, 'message' => 'Invalid device type'], 400);
        }

        $request->attributes->set('isDevice', $device);
        return $next($request);
    }
}
