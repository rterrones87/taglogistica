<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
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
        // Si es una creación (POST), validar todos los campos básicos
        if ($this->isMethod('POST')) {
            return $this->getCreationRules();
        }

        // Si es una actualización (PUT), las reglas dependen de los permisos
        if ($this->isMethod('PUT')) {
            return $this->getUpdateRules();
        }

        return [];
    }

    /**
     * Reglas para creación de servicios
     */
    private function getCreationRules(): array
    {
        return [
            // Campos principales del servicio
            'client_id' => 'required|integer|exists:clients,id',
            'type_operation' => 'required|integer|in:1,2,3',
            'terminal' => 'required|string|max:100',
            'dispatch_date' => 'required|date',
            'delivery_date' => 'required|date|after_or_equal:dispatch_date',
            'type_unit' => ['required', 'integer', Rule::exists('unit_types', 'id')->where('zombie', 0)],
            'IMO' => 'nullable|integer|in:0,1',
            
            // Contenedores
            'containers' => 'required|array|min:1',
            'containers.*.id' => 'nullable|integer|exists:containers,id',
            'containers.*.order_number' => 'required|string|max:100',
            'containers.*.container_number' => 'required|string|max:100',
            'containers.*.container_type' => 'required|string|max:100',
            'containers.*.place_id' => 'required|integer|exists:places,id',
            'containers.*.address' => 'required|string|max:255',
        ];
    }

    /**
     * Reglas para actualización de servicios (dinámicas según permisos)
     */
    private function getUpdateRules(): array
    {
        $rules = [];

        // Permisos del usuario
        $hasEditPermission = $this->userHasPermission('services.edit');
        $hasAssignPermission = $this->userHasPermission('services.assign');
        $hasAssignDieselPermission = $this->userHasPermission('services.assign_diesel');

        // Caso 1: Usuario con permiso de edición completa
        if ($hasEditPermission) {
            $rules = array_merge($rules, [
                'client_id' => 'required|integer|exists:clients,id',
                'type_operation' => 'required|integer|in:1,2,3',
                'terminal' => 'required|string|max:100',
                'dispatch_date' => 'required|date',
                'delivery_date' => 'required|date|after_or_equal:dispatch_date',
                'type_unit' => ['required', 'integer', Rule::exists('unit_types', 'id')->where('zombie', 0)],
                'IMO' => 'nullable|integer|in:0,1',
                'containers' => 'required|array|min:1',
                'containers.*.id' => 'nullable|integer|exists:containers,id',
                'containers.*.order_number' => 'required|string|max:100',
                'containers.*.container_number' => 'required|string|max:100',
                'containers.*.container_type' => 'required|string|max:100',
                'containers.*.place_id' => 'required|integer|exists:places,id',
                'containers.*.address' => 'required|string|max:255',
            ]);
        } else {
            // Si no tiene permiso de edición, estos campos son opcionales
            $rules = array_merge($rules, [
                'client_id' => 'nullable|integer|exists:clients,id',
                'type_operation' => 'nullable|integer|in:1,2,3',
                'terminal' => 'nullable|string|max:100',
                'dispatch_date' => 'nullable|date',
                'delivery_date' => 'nullable|date|after_or_equal:dispatch_date',
                'type_unit' => ['nullable', 'integer', Rule::exists('unit_types', 'id')->where('zombie', 0)],
                'IMO' => 'nullable|integer|in:0,1',
                'containers' => 'nullable|array',
                'containers.*.id' => 'nullable|integer|exists:containers,id',
                'containers.*.order_number' => 'nullable|string|max:100',
                'containers.*.container_number' => 'nullable|string|max:100',
                'containers.*.container_type' => 'nullable|string|max:100',
                'containers.*.place_id' => 'nullable|integer|exists:places,id',
                'containers.*.address' => 'nullable|string|max:255',
            ]);
        }

        // Caso 2: Usuario con permiso de asignación completa
        if ($hasAssignPermission) {
            $rules = array_merge($rules, [
                'operators'                             => 'required|array|min:1',
                'operators.*.service_operator_type_id' => 'required|integer|exists:service_operator_types,id',
                'operators.*.operator_id'              => 'required|integer|exists:users,id',
                'operators.*.unit_id'                  => 'required|integer|exists:units,id',
                'operators.*.rate_id'                  => 'nullable|integer|exists:service_operator_type_rates,id',
                'operators.*.diesel'                   => 'nullable|numeric|min:0',
            ]);
        } else {
            // Sin permiso de asignación, el array es opcional
            $rules = array_merge($rules, [
                'operators'                             => 'nullable|array',
                'operators.*.service_operator_type_id' => 'nullable|integer|exists:service_operator_types,id',
                'operators.*.operator_id'              => 'nullable|integer|exists:users,id',
                'operators.*.unit_id'                  => 'nullable|integer|exists:units,id',
                'operators.*.diesel'                   => 'nullable|numeric|min:0',
            ]);
        }

        // Caso 3: Validación de diesel
        // Solo es requerido si tiene permiso de diesel O si tiene permiso de asignación completa Y es una actualización
        // En otros casos, es opcional
        if ($hasAssignDieselPermission || $hasAssignPermission) {
            $rules['diesel'] = 'nullable|numeric|min:0';
        } else {
            $rules['diesel'] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    /**
     * Helper para verificar si el usuario tiene un permiso específico
     */
    private function userHasPermission(string $permission): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasPermission($permission);
    }

    /**
     * Preparar datos para validación (limpiar campos según permisos)
     * 
     * Este método filtra los campos del request según los permisos del usuario:
     * - Si NO tiene 'services.edit': remueve campos de edición (client_id, type_operation, etc.)
     * - Si NO tiene 'services.assign': remueve campos de asignación (unit_id, operator_id, etc.)
     * 
     * IMPORTANTE: Los campos se ELIMINAN completamente del request en lugar de convertirse a null,
     * ya que la BD espera valores default (0) y no acepta null en algunos campos como unit_id.
     */
    public function prepareForValidation(): void
    {
        // Solo aplica en actualizaciones
        if (!$this->isMethod('PUT')) {
            return;
        }

        $hasEditPermission = $this->userHasPermission('services.edit');
        $hasAssignPermission = $this->userHasPermission('services.assign');
        $hasAssignDieselPermission = $this->userHasPermission('services.assign_diesel');

        // Obtener todos los campos actuales del request
        $data = $this->all();

        // Si no tiene permiso de edición, remover completamente campos de edición
        if (!$hasEditPermission) {
            $editFields = [
                'client_id',
                'type_operation',
                'terminal',
                'dispatch_date',
                'delivery_date',
                'type_unit',
                'IMO',
                'containers'
            ];
            
            foreach ($editFields as $field) {
                unset($data[$field]);
            }
        }

        // Si no tiene permiso de asignación, remover el array de operadores
        if (!$hasAssignPermission) {
            unset($data['operators']);
        }

        if (!$hasAssignDieselPermission) {
            $assignDieselFields = [
                'diesel'
            ];
            
            foreach ($assignDieselFields as $field) {
                unset($data[$field]);
            }
        }

        // Reemplazar el request con los campos filtrados
        $this->replace($data);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Client
            'client_id.required' => 'El cliente es obligatorio',
            'client_id.integer' => 'El cliente debe ser un número válido',
            'client_id.exists' => 'El cliente seleccionado no existe',
            
            // Type operation
            'type_operation.required' => 'El tipo de operación es obligatorio',
            'type_operation.integer' => 'El tipo de operación debe ser un número válido',
            'type_operation.in' => 'El tipo de operación debe ser Importación (1), Exportación (2) o Carga Suelta (3)',
            
            // Terminal
            'terminal.required' => 'La terminal es obligatoria',
            'terminal.string' => 'La terminal debe ser texto',
            'terminal.max' => 'La terminal no puede exceder 100 caracteres',
            
            // Dates
            'dispatch_date.required' => 'La fecha de despacho es obligatoria',
            'dispatch_date.date' => 'La fecha de despacho debe ser una fecha válida',
            'delivery_date.required' => 'La fecha de entrega es obligatoria',
            'delivery_date.date' => 'La fecha de entrega debe ser una fecha válida',
            'delivery_date.after_or_equal' => 'La fecha de entrega debe ser igual o posterior a la fecha de despacho',
            
            // Type unit
            'type_unit.required' => 'El tipo de unidad es obligatorio',
            'type_unit.integer' => 'El tipo de unidad debe ser un número válido',
            'type_unit.exists' => 'El tipo de unidad seleccionado no es válido.',
            
            // IMO
            'IMO.integer' => 'El campo IMO debe ser un número válido',
            'IMO.in' => 'El campo IMO debe ser 0 o 1',
            
            // Containers array
            'containers.required' => 'Debe agregar al menos un contenedor',
            'containers.array' => 'Los contenedores deben ser una lista válida',
            'containers.min' => 'Debe agregar al menos un contenedor',
            
            // Container fields
            'containers.*.order_number.required' => 'La orden de compra es obligatoria en todos los contenedores',
            'containers.*.order_number.string' => 'La orden de compra debe ser texto',
            'containers.*.order_number.max' => 'La orden de compra no puede exceder 100 caracteres',
            
            'containers.*.container_number.required' => 'El número de contenedor es obligatorio',
            'containers.*.container_number.string' => 'El número de contenedor debe ser texto',
            'containers.*.container_number.max' => 'El número de contenedor no puede exceder 100 caracteres',
            
            'containers.*.container_type.string' => 'El tipo de contenedor debe ser texto',
            'containers.*.container_type.max' => 'El tipo de contenedor no puede exceder 100 caracteres',
            
            'containers.*.place_id.required' => 'El destino es obligatorio en todos los contenedores',
            'containers.*.place_id.integer' => 'El destino debe ser un número válido',
            'containers.*.place_id.exists' => 'El destino seleccionado no existe',
            
            'containers.*.address.string' => 'La dirección debe ser texto',
            'containers.*.address.max' => 'La dirección no puede exceder 255 caracteres',
            
            // Asignación - Operadores y Unidades
            'unit_id.required' => 'La unidad principal es obligatoria',
            'unit_id.integer' => 'La unidad principal debe ser un número válido',
            'unit_id.exists' => 'La unidad principal seleccionada no existe',
            
            'operator_id.required' => 'El operador principal es obligatorio',
            'operator_id.integer' => 'El operador principal debe ser un número válido',
            'operator_id.exists' => 'El operador principal seleccionado no existe',
            
            'aux_unit_id.required' => 'La unidad auxiliar es obligatoria',
            'aux_unit_id.integer' => 'La unidad auxiliar debe ser un número válido',
            'aux_unit_id.exists' => 'La unidad auxiliar seleccionada no existe',
            
            'aux_operator_id.required' => 'El operador auxiliar es obligatorio',
            'aux_operator_id.integer' => 'El operador auxiliar debe ser un número válido',
            'aux_operator_id.exists' => 'El operador auxiliar seleccionado no existe',
            
            'aux2_unit_id.required' => 'La unidad auxiliar 2 es obligatoria',
            'aux2_unit_id.integer' => 'La unidad auxiliar 2 debe ser un número válido',
            'aux2_unit_id.exists' => 'La unidad auxiliar 2 seleccionada no existe',
            
            'aux2_operator_id.required' => 'El operador auxiliar 2 es obligatorio',
            'aux2_operator_id.integer' => 'El operador auxiliar 2 debe ser un número válido',
            'aux2_operator_id.exists' => 'El operador auxiliar 2 seleccionado no existe',
            
            // Diesel
            'diesel.required' => 'El diesel es obligatorio',
            'diesel.numeric' => 'El diesel debe ser un número válido',
            'diesel.min' => 'El diesel debe ser mayor o igual a 0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'type_operation' => 'tipo de operación',
            'terminal' => 'terminal',
            'dispatch_date' => 'fecha de despacho',
            'delivery_date' => 'fecha de entrega',
            'type_unit' => 'tipo de unidad',
            'IMO' => 'IMO',
            'containers' => 'contenedores',
            'containers.*.order_number' => 'orden de compra',
            'containers.*.container_number' => 'número de contenedor',
            'containers.*.container_type' => 'tipo de contenedor',
            'containers.*.place_id' => 'destino',
            'containers.*.address' => 'dirección',
            'unit_id' => 'unidad principal',
            'operator_id' => 'operador principal',
            'aux_unit_id' => 'unidad auxiliar',
            'aux_operator_id' => 'operador auxiliar',
            'aux2_unit_id' => 'unidad auxiliar 2',
            'aux2_operator_id' => 'operador auxiliar 2',
            'diesel' => 'diesel',
        ];
    }
}
