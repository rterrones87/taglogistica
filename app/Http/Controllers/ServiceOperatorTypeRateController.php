<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceOperatorTypeRate;

class ServiceOperatorTypeRateController extends Controller
{
    /**
     * Lista tarifas, opcionalmente filtradas por service_operator_type_id.
     * GET /api/service-operator-type-rates?service_operator_type_id={id}
     */
    public function index(Request $request)
    {
        return ServiceOperatorTypeRate::where('zombie', 0)
            ->when(
                $request->service_operator_type_id,
                fn ($q) => $q->where('service_operator_type_id', $request->service_operator_type_id)
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * Crea una nueva tarifa.
     * POST /api/service-operator-type-rates
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_operator_type_id' => 'required|integer|exists:service_operator_types,id',
            'name'                     => 'required|string|max:100',
            'amount'                   => 'required|numeric|decimal:0,2|min:0.01',
        ], [
            'service_operator_type_id.required' => 'El tipo de operador es obligatorio',
            'service_operator_type_id.exists'   => 'El tipo de operador no existe',
            'name.required'                     => 'El nombre es obligatorio',
            'amount.required'                   => 'El monto es obligatorio',
            'amount.min'                        => 'El monto debe ser mayor a 0',
        ]);

        $rate = ServiceOperatorTypeRate::create($validated);

        return response()->json($rate, 201);
    }

    /**
     * Soft delete de una tarifa.
     * DELETE /api/service-operator-type-rates/{id}
     */
    public function destroy($id)
    {
        $rate = ServiceOperatorTypeRate::findOrFail($id);
        $rate->update(['zombie' => 1]);

        return response()->json(null, 204);
    }
}
