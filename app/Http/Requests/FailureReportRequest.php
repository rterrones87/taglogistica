<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FailureReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'exists:units,id'],
            'operator_id' => ['required', 'exists:users,id'],
            'mileage' => ['required', 'integer', 'min:1'],
            'reported_at' => ['required', 'date'],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }
}
