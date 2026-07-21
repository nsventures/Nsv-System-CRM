<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use function Laravel\Prompts\error;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class MultiGuardMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if Sanctum token is passed in the URL as a query parameter
        if ($request->has('token')) {
            $token = $request->query('token');

            // Set the Authorization header with the token
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }
        if (Auth::guard('web')->check() || Auth::guard('client')->check() || Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return formatApiResponse(false, 'Unauthorized', [], statusCode: 401);
        }
        // Store the intended URL and redirect to login
        Session::put('url.intended', $request->fullUrl());
        return redirect('/');
    }
}
