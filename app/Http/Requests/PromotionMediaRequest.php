<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class PromotionMediaRequest extends FormRequest 
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
        $isStoreRoute = $this->is('api/promotion-media/store');
        $isUpdateRoute = $this->is('api/promotion-media/update/*');

        $acceptedFormats = [
            // Imágenes
            'jpeg', 'jpg', 'png', 'gif', 'webp',
            
            // Videos
            'mp4', 'mov', 'webm', 'm4v', 'mkv', 'avi', 'wmv',
            'flv', '3gp', 'mpeg', 'mpg', 'ts'
        ];

        $rules = [
            'promotion_uuid' => ($isStoreRoute ? 'required|' : 'nullable|') . 'exists:promotions,uuid',
        ];

        // Para store: array de archivos
        if ($isStoreRoute) {
            $rules['business_promo_media_url'] = 'required|array';
            $rules['business_promo_media_url.*'] = 'required|file|mimes:' . implode(',', $acceptedFormats) . '|max:50240';
        }
        // Para update: archivo único
        else if ($isUpdateRoute) {
            $rules['business_promo_media_url'] = 'required|file|mimes:' . implode(',', $acceptedFormats) . '|max:50240';
        }

        return $rules;
    }

    /**
     * Handle failed validation
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'errors'    => $validator->errors()
        ], 422));
    }
}