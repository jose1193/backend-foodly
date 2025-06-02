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
        
        // New addresses field
        'addresses' => ['nullable', 'array'],
        'addresses.*.address' => ['required_with:addresses', 'string', 'max:255'],
        'addresses.*.city' => ['required_with:addresses', 'string', 'max:255'],
        'addresses.*.country' => ['required_with:addresses', 'string', 'max:255'],
        'addresses.*.zip_code' => ['required_with:addresses', 'string', 'max:20'],
        'addresses.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
        'addresses.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        'addresses.*.address_label_id' => ['required_with:addresses', 'exists:address_labels,id'],
        'addresses.*.principal' => ['boolean'],
        
        // Deprecated fields - kept for backward compatibility
        'address' => ['nullable', 'string', 'max:255'],
        'zip_code' => ['nullable', 'string', 'max:20'],
        'city' => ['nullable', 'string', 'max:255'],
        'country' => ['nullable', 'string', 'max:255'],
        'latitude' => ['nullable', 'numeric', 'between:-90,90'],
        'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        
        'terms_and_conditions' => ['nullable', 'boolean'],
        'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
        'role_id' => [$isStoreRoute ? 'required' : 'nullable', 'exists:roles,id'],
        'provider' => ['nullable', 'min:4', 'max:20'],
        'provider_id' => ['nullable', 'min:4', 'max:30'],
        'provider_avatar' => ['nullable', 'min:4', 'max:255'],
    ];
}

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}
