<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsResponsableDelDepartamento
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

public function handle(Request $request, Closure $next)
{
     $user = $request->user();
    // Permitir si es responsable del departamento o si es admin (role_id == 1)
    if (
        !$user ||
        (!$user->esResponsableDeSuDepartamento() && $user->role_id != 1)
    ) {
        return response()->json(['message' => 'No autorizado.'], 403);
    }
    return $next($request);
}


}
