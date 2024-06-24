<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BusinessRequest extends FormRequest
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
        // Determine if the current route is 'api/business-store'
        $isStoreRoute = $this->is('api/business-store');

        return [
            'business_name' => ($isStoreRoute ? 'required|' : '') . 'string|min:3|max:255',
            'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048',
            'business_email' => 'nullable|email',
            'business_phone' => 'nullable|string|min:5|max:50',
            'business_address' => 'nullable|string|min:5|max:50',
            'business_zipcode' => 'nullable|string|min:2|max:50',
            'business_city' => 'nullable|string|min:3|max:50',
            'business_country' => 'nullable|string|min:3|max:50',
            'business_website' => 'nullable|string',
            'business_latitude' => 'nullable', 
            'business_longitude' => 'nullable',
            'business_about_us' => 'nullable',
            'business_services' => 'nullable|array',
            'business_services.*' => 'nullable|integer|exists:services,id',


            'business_additional_info' => 'nullable',
            'category_id' => ($isStoreRoute ? 'required|' : '') . 'exists:categories,id',

            //'business_hours.*.day' => 'nullable|string|min:0|max:6',
            //'business_hours.*.open_a' => 'nullable|string',
            //'business_hours.*.close_a' => 'nullable|string',
            //'business_hours.*.open_b' => 'nullable|string',
            //'business_hours.*.close_b' => 'nullable|string',

            
            'business_hours.*.open_a' => 'nullable|string',
            'business_hours.*.open_b' => 'nullable|string',
            'business_hours.*.close_a' => 'nullable|string',
            'business_hours.*.close_b' => 'nullable|string',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'errors'    => $validator->errors()
        ], 422));
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'The specified category does not exist.',
        ];
    }
}