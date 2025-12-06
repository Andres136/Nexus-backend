<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
public function handle($request, Closure $next)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'Not authenticated'], 401);
    }

    $path = '/' . $request->path();

    if (!$user->permissions()->pluck('path')->contains($path)) {
        return response()->json(['error' => 'No autorizado'], 403);
    }

    return $next($request);
}

    


}
