<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO Sprint 6/7: Client-Side Auth / Public Key Validation
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:card'],
            'card' => ['required_if:type,card', 'array'],
            'card.number' => ['required_if:type,card', 'string'],
            'card.exp_month' => ['required_if:type,card', 'string', 'max:2'],
            'card.exp_year' => ['required_if:type,card', 'string'],
            'card.cvv' => ['required_if:type,card', 'string'],
            'card.holder_name' => ['required_if:type,card', 'string'],
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
