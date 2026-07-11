<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO Sprint 6/7: S2S Auth / Secret Key Validation
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'in:BRL'],
            'reference_id' => ['required', 'string', 'max:255'],
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string'],
            'customer.email' => ['required', 'email'],
            'customer.document' => ['required', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'error' => [
                'type' => 'validation_error',
                'message' => 'The given data was invalid.',
                'fields' => $validator->errors(),
            ],
        ], 422));
    }
}
