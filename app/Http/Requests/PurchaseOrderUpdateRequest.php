<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('maintenances.edit');
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
            'quotation' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'evidences' => ['nullable', 'array', 'max:5'],
            'evidences.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'deleted_file_ids' => ['nullable', 'array'],
            'deleted_file_ids.*' => ['integer', 'distinct', 'exists:file_purchase_orders,id'],
        ];
    }
}
