<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class PromotionRequest extends FormRequest
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
         // Determine if the current route is 'api/promotions-store'
        $isStoreRoute = $this->is('api/promotions/store');

       return [
            'business_uuid' => ($isStoreRoute ? 'required|' : 'nullable|') . 'exists:businesses,business_uuid',
            'title' => ($isStoreRoute ? 'required|' : '') . 'string|min:3|max:255',
            'sub_title' => 'nullable|min:3|string|max:255',
            'description' => 'nullable|min:3|string|max:255',
            'start_date' => 'nullable|string|min:3|max:50',
            'expire_date' => 'nullable|string|min:3|max:50',
            'media_link' => 'nullable|string|max:255',
            'versions' => 'nullable|array',
            'prices' => 'nullable|array',
            'prices.regular' => 'nullable|numeric|min:0',
            'prices.medium' => 'nullable|numeric|min:0',
            'prices.big' => 'nullable|numeric|min:0',
            'favorites_count' => 'nullable|integer|min:0',
            'available' => 'nullable|boolean',

            'promo_active_days.*.day_0' => 'nullable',
            'promo_active_days.*.day_1' => 'nullable',
            'promo_active_days.*.day_2' => 'nullable',
            'promo_active_days.*.day_3' => 'nullable',
            'promo_active_days.*.day_4' => 'nullable',
            'promo_active_days.*.day_5' => 'nullable',
            'promo_active_days.*.day_6' => 'nullable',
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
