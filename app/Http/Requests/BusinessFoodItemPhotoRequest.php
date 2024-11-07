<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BusinessFoodItemPhotoRequest extends FormRequest
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
        $isStoreRoute = $this->is('api/business-food-item-photos/store');
        $isUpdateRoute = $this->isMethod('POST') && $this->is('*/update/*');

        if ($isStoreRoute) {
            return [
                'business_food_photo_url' => 'required|array',
                'business_food_photo_url.*' => 'required',
                'business_food_item_id' => 'required|exists:business_food_items,id',
            ];
        }

        if ($isUpdateRoute) {
            return [
                'business_food_photo_url' => ['required', function ($attribute, $value, $fail) {
                    // Check if it's a file
                    if ($this->hasFile('business_food_photo_url')) {
                        return;
                    }
                    
                    // Check if it's base64
                    if (is_string($value) && preg_match('/^data:image\/(\w+);base64,/', $value)) {
                        return;
                    }
                    
                    // Check if it's binary
                    if (is_string($value) && strlen($value) > 0) {
                        return;
                    }
                    
                    $fail('The business_food_photo_url must be a file, base64 string, or binary data.');
                }],
            ];
        }

        // Default rules if neither store nor update
        return [
            'business_food_photo_url' => 'required',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'business_food_photo_url' => 'image',
            'business_food_item_id' => 'food item ID'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_food_photo_url.required' => 'The image is required.',
            'business_food_photo_url.array' => 'The image must be an array.',
            'business_food_photo_url.*.required' => 'Each image in the array is required.',
            'business_food_item_id.required' => 'The food item ID is required.',
            'business_food_item_id.exists' => 'The selected food item does not exist.'
        ];
    }

    /**
     * Handle failed validation
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
}