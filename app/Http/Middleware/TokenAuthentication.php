<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TokenAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  array  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // Verifica si el token existe en la cookie y lo agrega a los encabezados de la solicitud.
        if ($token = $request->cookie('token')) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        // Autentica la solicitud usando los guardias especificados.
        Auth::shouldUse($guards[0] ?? null);
        $this->authenticate($request, $guards);

        // Pasa la solicitud al siguiente middleware.
        return $next($request);
    }

    /**
     * Autentica la solicitud utilizando los guardias especificados.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  ...$guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate(Request $request, array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::shouldUse($guard);
            }
        }

        Auth::shouldUse($guards[0] ?? null);
    }
}
