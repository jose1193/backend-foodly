<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordResetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
         return [
        'token' => 'required|string|exists:password_reset_users,token',      
        //'email' => 'required|email|exists:users,email',
        'password' => [
            'required',
            'string',
            'confirmed',
            Password::min(5)  // Define una longitud mínima de 5 caracteres
                    ->mixedCase()  // Requiere mayúsculas y minúsculas
                    ->numbers()    // Requiere al menos un número
                    ->symbols()    // Requiere al menos un símbolo
                    ->uncompromised(),  // Verifica que la contraseña no haya sido expuesta en filtraciones de datos conocidas
        ],
    ];
    }


    public function failedValidation(Validator $validator)

    {

        throw new HttpResponseException(response()->json([

            'success'   => false,

            'message'   => 'Validation errors',

            'errors'      => $validator->errors()

        ], 422));

    }
}
