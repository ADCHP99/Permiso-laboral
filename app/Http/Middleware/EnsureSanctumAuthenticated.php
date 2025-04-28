<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSanctumAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->User()) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        return $next($request);
    }
}


