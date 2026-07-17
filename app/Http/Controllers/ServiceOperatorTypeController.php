<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceOperatorType;
use App\Models\Substate;
use App\Models\Service;

class ServiceOperatorTypeController extends Controller
{
    /**
     * Lista los tipos de operador, opcionalmente filtrados por tipo de operación.
     * GET /api/service-operator-types?type_operation={id}
     */
    public function index(Request $request)
    {
        return ServiceOperatorType::where('zombie', 0)
            ->when($request->type_operation, fn ($q) => $q->where('type_operation', $request->type_operation))
            ->with('rates')
            ->orderBy('id')
            ->get();
    }

    /**
     * Retorna los subestados del tipo de operación del servicio dado.
     * Usado por statebutton.vue para cargar la lista de subestados de forma dinámica.
     * GET /api/substates/for-service/{id}
     */
    public function forService($id)
    {
        $service = Service::findOrFail($id);

        return Substate::where('type_operation', $service->type_operation)
            ->where('zombie', 0)
            ->orderBy('id')
            ->get(['id', 'name']);
    }
}
