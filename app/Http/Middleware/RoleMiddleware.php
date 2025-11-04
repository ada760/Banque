<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user has admin role
        if (in_array('admin', $roles) && $user->admin) {
            return $next($request);
        }

        // Check if user has client role
        if (in_array('client', $roles) && $user->client) {
            return $next($request);
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }
}
