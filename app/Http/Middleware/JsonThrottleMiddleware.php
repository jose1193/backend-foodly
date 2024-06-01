<?php

namespace App\Http\Middleware;
use App\Models\User;
use App\Models\PasswordResetUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifySuspiciousResetPasswordActivity;
use App\Jobs\SendMailNotifySuspiciousResetPassword;


class JsonThrottleMiddleware
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle($request, Closure $next, $key, $maxAttempts)
    {
        $key = $key ?: $request->ip();
        
        // Capturar el correo electrónico asociado con el intento de recuperación de contraseña
        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            
        // Envía una notificación por correo electrónico al usuario
        if ($email && $user) {
            // Envía la notificación
            //Mail::to($email)->send(new NotifySuspiciousResetPasswordActivity($user));
            SendMailNotifySuspiciousResetPassword::dispatch($user);
            // Eliminar la entrada de la tabla password_reset_users
            PasswordResetUser::where('email', $email)->delete();
        }

            return new JsonResponse([
                'message' => 'Too Many Attempts. Please request a new PIN.',
                'retry_after' => $this->limiter->availableIn($key)
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

       

        $this->limiter->hit($key);

        $response = $next($request);

        if ($response instanceof JsonResponse && $response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
            $response->setStatusCode(Response::HTTP_TOO_MANY_REQUESTS, 'Too Many Attempts.');
            $response->setData([
                'message' => 'Too Many Attempts.',
                'retry_after' => $this->limiter->availableIn($key)
            ]);
        }

        return $response;
    }

}
