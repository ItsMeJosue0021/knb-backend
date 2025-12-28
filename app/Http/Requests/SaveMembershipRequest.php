<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveMembershipRequest extends FormRequest
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
            'proof_of_payment' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'proof_of_identity' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'payment_reference_number' => ['required', 'string', 'max:255'],
        ];
    }
}
