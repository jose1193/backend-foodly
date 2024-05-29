<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticateRoutes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Verifica si el método de la solicitud es GET, PUT o POST
      
        if (in_array($request->method(), ['GET', 'PUT', 'POST', 'DELETE', 'PATCH'])) {
            // Obtiene el token de la solicitud
            $token = $request->bearerToken();

            if ($token) {
                // Intenta recuperar el token
                $accessToken = PersonalAccessToken::findToken($token);

                if ($accessToken) {
                    // Establece el usuario autenticado en la solicitud
                    $request->user = $accessToken->tokenable;
                    Auth::setUser($accessToken->tokenable);
                } else {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }
            } else {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        }

        return $next($request);
    }
}
