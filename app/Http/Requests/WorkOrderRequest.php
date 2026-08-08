<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleCategories = ['Tractor', 'Remolque', 'Dolly', 'Plataforma', 'Caja Refrigerada'];
        return [
            'failure_report_id' => ['nullable', 'exists:failure_reports,id'],
            'unit_category' => ['required', Rule::in([...$vehicleCategories, 'Gastos de accidentes', 'Gastos de gruas', 'Mala operacion del operador', 'Rescate carretero'])],
            'maintenance_type' => [Rule::requiredIf(in_array($this->unit_category, $vehicleCategories, true)), 'nullable', Rule::in(['Preventivo', 'Correctivo'])],
            'unit_id' => ['required', 'exists:units,id'],
            'initial_mileage' => ['required', 'integer', 'min:1'],
            'opened_at' => ['required', 'date'],
            'operator_id' => ['required', 'exists:users,id'],
            'mechanic_id' => ['required', Rule::exists('users', 'id')->where(fn($query) => $query->where('role_id', 8)->where('active', 1)->where('zombie', 0))],
            'failure_description' => ['required', 'string', 'max:5000'],
            'work_type' => ['required', Rule::in(['Interno', 'Externo'])],
            'supplier_id' => [Rule::requiredIf($this->work_type === 'Externo'), 'nullable', 'exists:suppliers,id'],
        ];
    }
}
