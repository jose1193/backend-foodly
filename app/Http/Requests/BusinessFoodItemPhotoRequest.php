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
                    // Verifica si es un archivo
                    if ($this->hasFile('business_food_photo_url')) {
                        return;
                    }
                    
                    // Verifica si es base64
                    if (is_string($value) && preg_match('/^data:image\/(\w+);base64,/', $value)) {
                        return;
                    }
                    
                    // Verifica si es binary
                    if (is_string($value) && strlen($value) > 0) {
                        return;
                    }
                    
                    $fail('El campo business_food_photo_url debe ser un archivo, base64 o datos binarios.');
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
            'business_food_photo_url' => 'imagen',
            'business_food_item_id' => 'ID del ítem de comida'
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
            'business_food_photo_url.required' => 'La imagen es requerida.',
            'business_food_item_id.required' => 'El ID del ítem de comida es requerido.',
            'business_food_item_id.exists' => 'El ítem de comida seleccionado no existe.'
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