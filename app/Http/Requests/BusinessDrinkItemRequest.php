<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class BusinessDrinkItemRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         $isStoreRoute = $this->is('api/business-drink-item/store');
    return [
        'business_drink_category_id' => ($isStoreRoute ? 'required|' : '') . 'exists:business_drink_categories,id',
        'name' => ($isStoreRoute ? 'required|' : '') . 'string|min:3|max:255',
        'description' => 'nullable|string|max:1000',
        'versions' => 'nullable|array',
        'prices' => 'nullable|array',
        'prices.regular' => 'nullable|numeric|min:0',
        'prices.medium' => 'nullable|numeric|min:0',
        'prices.big' => 'nullable|numeric|min:0',
        'favorites_count' => 'nullable|integer|min:0',
        'available' => ($isStoreRoute ? 'required|' : '') . 'boolean',
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
