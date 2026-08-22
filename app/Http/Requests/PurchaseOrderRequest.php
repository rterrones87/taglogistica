<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_order_id' => ['required', Rule::exists('work_orders', 'id')->where(fn ($query) => $query->where('status', 'En Proceso'))],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'description' => ['required', 'string', 'max:5000'],
            'cost' => ['required', 'numeric', 'min:0.01'],
            'payment_condition' => ['nullable', Rule::in(['Contado', 'Credito'])],
            'credit_days' => [Rule::requiredIf($this->payment_condition === 'Credito'), 'nullable', 'integer', 'min:1', 'max:3650'],
            'quotation' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'evidences' => ['required', 'array', 'min:1', 'max:5'],
            'evidences.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
