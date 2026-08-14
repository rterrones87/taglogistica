<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderPaymentConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_condition' => ['nullable', Rule::in(['Contado', 'Credito'])],
            'credit_days' => [
                Rule::requiredIf($this->payment_condition === 'Credito'),
                'nullable',
                'integer',
                'min:1',
                'max:3650',
            ],
        ];
    }
}
