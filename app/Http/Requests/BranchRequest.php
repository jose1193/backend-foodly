<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BranchRequest extends FormRequest
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
         // Determine if the current route is 'api/branch-store'
        $isStoreRoute = $this->is('api/branch-store');

        return [
            
            'branch_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
            'branch_name' => ($isStoreRoute ? 'required|' : '') . 'string|min:3|max:255',
            'branch_email' => 'nullable|email',
            'branch_phone' => 'nullable|string|min:5|max:50',
            'branch_address' => 'nullable|string|min:5|max:50',
            'branch_zipcode' => 'nullable|string|min:2|max:50',
            'branch_city' => 'nullable|string|min:3|max:50',
            'branch_country' => 'nullable|string',
            'branch_website' => 'nullable|string',
            'branch_latitude' => 'nullable',
            'branch_longitude' => 'nullable',
            'branch_services' => 'nullable|array',
            'branch_services.*' => 'nullable|integer|exists:services,id',
            'branch_additional_info' => 'nullable',
            'branch_about_us' => 'nullable',
            'business_id' => ($isStoreRoute ? 'required|' : '') . 'exists:businesses,id',
            
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
