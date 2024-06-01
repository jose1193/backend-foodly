<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\User;
use App\Models\PasswordResetUser;
use Illuminate\Http\Request;
use App\Http\Requests\ForgotPasswordUserRequest;
use App\Http\Requests\PasswordResetUserRequest;

use App\Http\Resources\ForgotPasswordUserResource;
use App\Http\Resources\PasswordResetUserResource;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Mail\ResetPasswordMail;
use App\Mail\NotifySuspiciousResetPasswordActivity;
use App\Mail\PasswordResetSuccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UpdatePasswordResetRequest;
use Illuminate\Support\Facades\Log;

use App\Jobs\SendMailResetPassword;
use App\Jobs\SendMailPasswordResetSuccess;



class PasswordResetUserController extends BaseController
{

public function __construct()
    {
        // Aplicar throttle solo al método verifyResetPassword
        $this->middleware('json_throttle:reset_password,4')->only('verifyResetPassword');
    }




    
public function store(ForgotPasswordUserRequest $request)
{
    // Validar los datos del formulario
    $validatedData = $request->validated();
    
    DB::beginTransaction();
    
    try {
        // Buscar al usuario por su correo electrónico
        $user = User::where('email', $validatedData['email'])->firstOrFail();
        
        // Generar un token de manera personalizada
        $token = bin2hex(random_bytes(19));  // Asegura una longitud adecuada y la seguridad del token
        
      
        // Generar un PIN de 4 dígitos
        $pin = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // Crear un nuevo registro en PasswordResetUser
        $passwordResetUser = PasswordResetUser::create([
            'email' => $validatedData['email'],
            'token' => $token,
            'pin' => $pin,
           
        ]);

        // Enviar correo electrónico al usuario
        //Mail::to($user->email)->send(new ResetPasswordMail($pin));
        SendMailResetPassword::dispatch($user->email,$pin);

        DB::commit();

        // Devolver una respuesta adecuada
        return response()->json([
            'message' => 'Reset password instructions sent successfully',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new ForgotPasswordUserResource($passwordResetUser)
        ], 200);

    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'User not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otro error y registrar el mensaje de error
        DB::rollBack();
        Log::error('An error occurred during the forgot password process: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred during the forgot password process'], 500);
    }
}



public function verifyResetPassword(PasswordResetUserRequest $request)
{
    // Validar los datos del formulario
    $validatedData = $request->validated();
  

    DB::beginTransaction();
    try {
        // Buscar el registro de restablecimiento de contraseña usando el token
        $passwordReset = PasswordResetUser::where('token', $validatedData['token'])->first();

    
        // Verificar si el registro existe y si el PIN coincide
        if (!$passwordReset || $passwordReset->pin !== $validatedData['pin']) {
            // No existe el token o el PIN no coincide
            DB::rollBack();
            return response()->json(['message' => 'Invalid token or PIN.'], 422);
        }

        // Verificar si el PIN ha caducado (30 minutos)
        if ($passwordReset->created_at->lt(Carbon::now()->subMinutes(30))) {
            // PIN caducado
             $this->cleanupPasswordResetRecord($passwordReset->email);
            DB::rollBack();
            return response()->json(['message' => 'Your PIN has expired. Please request a new one.'], 422);
        }

        
        // Actualizar pin_verified_at con la marca de tiempo actual
        $passwordReset->pin_verified_at = now();
        $passwordReset->save();
        
        DB::commit();

        
        return response()->json([
            'message' => 'PIN verified. Proceed with password reset.',
            'user' => new PasswordResetUserResource($passwordReset)
        ]);
    } catch (\Exception $e) {
        // Manejar cualquier otro error y registrar el mensaje de error
        DB::rollBack();
        Log::error('An error occurred while verifying the PIN: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while verifying the PIN.', 'error' => $e->getMessage()], 500);
    }
}


public function updatePassword(UpdatePasswordResetRequest $request)
{
    $validated = $request->validated();

    
    try {
        DB::transaction(function () use ($validated) {
            $passwordReset = PasswordResetUser::where('token', $validated['token'])->firstOrFail();
            $this->validatePinVerification($passwordReset);
            $user = User::where('email', $passwordReset->email)->firstOrFail();
           

            $user->password = Hash::make($validated['password']);
            $user->save();
            $this->cleanupPasswordResetRecord($passwordReset->email);
           

            // Envía el correo electrónico de confirmación de cambio de contraseña
            //Mail::to($user->email)->send(new PasswordResetSuccess($user));
            SendMailPasswordResetSuccess::dispatch($user);
        });

        return response()->json(['message' => 'Password updated successfully'], 200);
    }  catch (\Throwable $e) {
    // Manejar cualquier tipo de excepción y devolver una respuesta de error
    Log::error('Failed to update password: ' . $e->getMessage());
    return response()->json(['message' => 'Failed to update password: ' . $e->getMessage()], 500);
}
}


protected function validatePinVerification($passwordReset)
{
    // Verifica que el campo pin_verified_at no esté vacío y que no hayan pasado más de 60 minutos
    if (empty($passwordReset->pin_verified_at) || Carbon::parse($passwordReset->pin_verified_at)->addMinutes(60)->isPast()) {
         $this->cleanupPasswordResetRecord($passwordReset->email);
        throw new \Exception('The verification window has expired or pin verification is missing.');
    }
}




protected function cleanupPasswordResetRecord($email)
{
    PasswordResetUser::where('email', $email)->delete();
}



}
