<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGCashDonationRequest extends FormRequest
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
        $channel = $this->input('payment_channel', 'gateway');

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_channel' => ['nullable', Rule::in(['gateway', 'qr'])],
            'payment_reference_number' => [
                Rule::requiredIf($channel === 'qr'),
                'nullable',
                'string',
                'max:255',
            ],
            'proof_of_payment' => [
                Rule::requiredIf($channel === 'qr'),
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png',
                'max:5120',
            ],
        ];
    }

    public function messages()
    {
        return [
            'amount.required' => 'Please enter the donation amount.',
            'amount.numeric' => 'The donation amount must be a number.',
            'payment_reference_number.required' => 'Please enter the payment reference number.',
            'proof_of_payment.required' => 'Please upload the proof of payment.',
            'proof_of_payment.image' => 'The proof of payment must be an image.',
            'proof_of_payment.mimes' => 'The proof of payment must be a JPG or PNG image.',
            'proof_of_payment.max' => 'The proof of payment must not exceed 5MB.',
        ];
    }
}
