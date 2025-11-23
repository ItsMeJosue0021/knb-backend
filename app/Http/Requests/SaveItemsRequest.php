<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveItemsRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category' => [
                'required',
                'integer',
                'exists:g_d_categories,id',
            ],

            'sub_category' => [
                'required',
                'integer',
                'exists:g_d_subcategories,id',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'unit' => [
                'nullable',
                'string',
                'max:50',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048', 
            ],
        ];
    }
}
