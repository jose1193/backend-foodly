<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class HaversineSearchRequest extends FormRequest
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
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'voice_text' => 'nullable|string|max:1000',
            'radius' => 'nullable|numeric|min:0',
            'distance_unit' => 'nullable|string|in:km,mi',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
        'latitude.required' => 'Latitude is required for the search',
        'latitude.between' => 'Latitude must be between -90 and 90 degrees',
        'longitude.required' => 'Longitude is required for the search',
        'longitude.between' => 'Longitude must be between -180 and 180 degrees',
        'voice_text.max' => 'Voice text cannot exceed 1000 characters',
        'radius.numeric' => 'Radius must be a numeric value',
        'radius.min' => 'Radius must be greater than 0',
        'distance_unit.in' => 'Distance unit must be km or mi',
    ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     *
     * @throws HttpResponseException
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