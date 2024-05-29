<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CategoryRequest extends FormRequest
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
       $categoryId = $this->route('category') ? $this->route('category')->id : null;

    return [
         'category_name' => 'required|string|min:3|max:255|unique:categories,category_name,' . $categoryId . ',id',
        'category_description' => 'nullable|string|max:255',
        'category_image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3048',
       
        
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
        'category_name.unique' => 'The category name is already in use.',
        
    ];
}
}
