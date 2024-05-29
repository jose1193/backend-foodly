<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class SubcategoryRequest extends FormRequest
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
     public function rules()
{
    $subcategoryId = $this->route('subcategory') ? $this->route('subcategory')->id : null;
    $isStoreRoute = $this->is('api/subcategories-store');

    return [
        'subcategory_name' => ($isStoreRoute ? 'required|' : '') . 'string|min:3|max:255|unique:subcategories,subcategory_name,' . $subcategoryId . ',id',
        'subcategory_description' => 'nullable|string|min:3|max:255',
        'category_id' => ($isStoreRoute ? 'required|' : '') . 'exists:categories,id',
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


    
    public function messages()
{
    return [
        'subcategory_name.unique' => 'The subcategory name is already in use.',
        'user_id.exists' => 'The specified user does not exist.',
    ];
}
}
