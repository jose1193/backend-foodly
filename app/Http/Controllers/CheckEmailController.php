<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Routing\Controller as BaseController;

class CheckEmailController extends BaseController
{
    public function checkEmailAvailability($email)
    {
        // Validar el formato del correo electrónico
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // El formato del correo electrónico es inválido
            return response()->json(['error' => 'Invalid email format'], 400);
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            // El correo electrónico ya está en uso
            return response()->json(['email' => 'unavailable']);
        } else {
            // El correo electrónico está disponible
            return response()->json(['email' => 'available']);
        }
    }
}

