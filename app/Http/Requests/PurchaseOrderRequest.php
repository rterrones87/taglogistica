<?php

namespace App\Http\Requests;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = WorkOrder::find($this->work_order_id);
        return !$order || $order->status !== 'Cerrado';
    }

    public function rules(): array
    {
        return [
            'work_order_id' => ['required', 'exists:work_orders,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'description' => ['required', 'string', 'max:5000'],
            'cost' => ['required', 'numeric', 'min:0.01'],
            'payment_condition' => ['nullable', Rule::in(['Contado', 'Credito'])],
            'credit_days' => [Rule::requiredIf($this->payment_condition === 'Credito'), 'nullable', 'integer', 'min:1', 'max:3650'],
            'quotation' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
