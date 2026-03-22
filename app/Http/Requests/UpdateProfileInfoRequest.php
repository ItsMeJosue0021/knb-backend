<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileInfoRequest extends FormRequest
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
            'first_name' => 'nullable|string|max:255',
            'firstName' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'middleName' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'email' => 'required|email',
            'contactNo' => 'nullable|string|max:20',
            'contact_number' => 'nullable|string|max:20',
            'username' => 'required|string|max:255',
            'block' => 'nullable|string',
            'lot' => 'nullable|string',
            'street' => 'nullable|string',
            'subdivision' => 'nullable|string',
            'barangay' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'steet' => 'nullable|string',
            'dubdivision' => 'nullable|string',
            'baranggy' => 'nullable|string',
        ];
    }
}
