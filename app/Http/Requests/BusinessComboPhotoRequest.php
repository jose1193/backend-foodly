<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BusinessComboPhotoRequest extends FormRequest
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
        $isStoreRoute = $this->is('api/business-combos-photos/store');
        $isUpdateRoute = $this->isMethod('POST') && $this->is('*/update/*');

        if ($isStoreRoute) {
            return [
                'business_combos_photo_url' => 'required|array',
                'business_combos_photo_url.*' => 'required',
                'business_combos_id' => 'required|exists:business_combos,id',
            ];
        }

        if ($isUpdateRoute) {
            return [
                'business_combos_photo_url' => 'required',
            ];
        }

        // Default rules if neither store nor update
        return [
            'business_combos_photo_url' => 'required',
        ];
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