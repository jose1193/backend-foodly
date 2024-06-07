<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        // Lista de métodos HTTP que requieren autenticación
        $methods = ['GET', 'PUT', 'POST', 'DELETE', 'PATCH'];

        // Verifica si el método de la solicitud está en la lista de métodos que requieren autenticación
        if (in_array($request->method(), $methods)) {
            // Obtiene el token de la cookie 'token'
            $token = $request->cookie('token');

            if ($token) {
                // Intenta recuperar el token de la base de datos
                $accessToken = PersonalAccessToken::findToken($token);

                if ($accessToken && !$this->tokenExpired($accessToken)) {
                    // Establece el usuario autenticado en la solicitud
                    $request->setUserResolver(function () use ($accessToken) {
                        return $accessToken->tokenable;
                    });
                    Auth::setUser($accessToken->tokenable);
                } else {
                    // Si el token no se encuentra o ha expirado, retorna un error de autenticación
                    return response()->json(['message' => 'Unauthenticated. Invalid or expired token.'], 401);
                }
            } else {
                // Si no hay token en la cookie, retorna un error de autenticación
                return response()->json(['message' => 'Unauthenticated. No token provided.'], 401);
            }
        }

        // Pasa la solicitud al siguiente middleware
        return $next($request);
    }

    /**
     * Verifica si el token ha expirado.
     *
     * @param  \Laravel\Sanctum\PersonalAccessToken  $token
     * @return bool
     */
    protected function tokenExpired(PersonalAccessToken $token)
    {
        return $token->expires_at && $token->expires_at->isPast();
    }
}
