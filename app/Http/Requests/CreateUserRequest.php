<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

use Illuminate\Validation\Rules\Password;

class CreateUserRequest extends FormRequest
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
    // Verifica si la ruta actual es 'api/store'
    $isStoreRoute = request()->routeIs('api/register');

    return [
        'name' => [$isStoreRoute ? 'required' : 'nullable', 'string', 'max:40', 'regex:/^[a-zA-Z\s]+$/'],
        'last_name' => ['nullable', 'string', 'max:40', 'regex:/^[a-zA-Z\s]+$/'],
        'username' => [ $isStoreRoute ? 'required' : 'nullable', 'string', 'max:30', 'unique:users', 'regex:/^[a-zA-Z0-9_]+$/'],
        'date_of_birth' => ['nullable', 'string', 'max:255'],
        'email' => [$isStoreRoute ? 'required' : 'nullable', 'string', 'email', 'min:10', 'max:255', 'unique:users'],
        'password' => [
            'nullable',
            'string',
            Password::min(5)->mixedCase()->numbers()->symbols()->uncompromised(),
        ],
        'phone' => ['nullable', 'string', 'min:4', 'max:20'],
        'address' => ['nullable', 'string', 'max:255'],
        'zip_code' => ['nullable', 'string', 'max:20'],
        'city' => ['nullable', 'string', 'max:255'],
        'country' => ['nullable', 'string', 'max:255'],
        'terms_and_conditions' => ['nullable', 'boolean'],
        'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
        'role_id' => [$isStoreRoute ? 'required' : 'nullable', 'exists:roles,id'],
        'provider' => ['nullable', 'min:4', 'max:20'],
        'provider_id' => ['nullable', 'min:4', 'max:30'],
        'provider_avatar' => ['nullable', 'min:4', 'max:255'],
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
