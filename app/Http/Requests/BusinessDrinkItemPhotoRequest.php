<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BusinessDrinkItemPhotoRequest extends FormRequest
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
        $method = $this->method();

        if ($method === 'POST') {
            return [
                'business_drink_photo_url' => 'required|array',
                'business_drink_photo_url.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:10048',
                'business_drink_item_id' => 'required|exists:business_drink_items,id',
            ];
        }

        return [
            'business_drink_photo_url' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:10048',
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