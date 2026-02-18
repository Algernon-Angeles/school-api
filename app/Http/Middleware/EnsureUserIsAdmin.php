<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (property_exists($user, 'is_active') && $user->is_active === false) {
            return response()->json(['message' => 'Account disabled'], 403);
        }

        return $next($request);
    }
}
