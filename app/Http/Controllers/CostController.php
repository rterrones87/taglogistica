<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Cost;
use App\Models\Container;
use App\Models\Expense;
use App\Models\Service;
use App\Models\ClientPlace;
use Illuminate\Support\Facades\DB;
use App\Models\TreasuryService;
use App\Helpers\NotificationHelper;

class CostController extends Controller
{
    /**
     * Retorna los destinos con casetas autorellenadas desde la plantilla,
     * o null si no aplica autorellenado.
     */
    private function autofillBoothsFromTemplate(Cost $cost)
    {
        // Solo ejecutar si el servicio está en estado "En Espera" (state_id = 1)
        if (!$cost->service || $cost->service->state_id != 1) {
            return null;
        }

        // Obtener formatted_destinations actual
        $currentDestinations = $cost->formatted_destinations;

        // Verificar si todos los destinos tienen booths vacíos
        $hasEmptyBooths = collect($currentDestinations)->every(function ($destination) {
            return empty($destination['booths']);
        });

        // Si no todos los destinos tienen booths vacíos, no autorellenar
        if (!$hasEmptyBooths || empty($currentDestinations)) {
            return null;
        }

        $clientId = $cost->service->client_id;
        $placeIds = collect($currentDestinations)->pluck('place_id')->unique()->toArray();

        // Obtener las plantillas de casetas para este cliente, sus destinos y el tipo de unidad del servicio
        $templates = ClientPlace::where('client_id', $clientId)
            ->whereIn('place_id', $placeIds)
            ->where('type_unit_id', $cost->service->type_unit)
            ->where('zombie', 0)
            ->with('booths.booth')
            ->get()
            ->keyBy('place_id');

        if ($templates->isEmpty()) {
            return null;
        }

        // Mapear las casetas de la plantilla a los destinos actuales
        return collect($currentDestinations)->map(function ($destination) use ($templates) {
            $placeId = $destination['place_id'];

            if (isset($templates[$placeId]) && $templates[$placeId]->booths->isNotEmpty()) {
                $booths = $templates[$placeId]->booths;

                $destination['booths_outbound'] = $booths
                    ->where('direction', 'outbound')
                    ->map(fn($b) => [
                        'booth_id' => (int) $b->booth_id,
                        'name'     => optional($b->booth)->name,
                        'cost'     => optional($b->booth)->cost ?? 0,
                    ])
                    ->values()
                    ->toArray();

                $destination['booths_return'] = $booths
                    ->where('direction', 'return')
                    ->map(fn($b) => [
                        'booth_id' => (int) $b->booth_id,
                        'name'     => optional($b->booth)->name,
                        'cost'     => optional($b->booth)->cost ?? 0,
                    ])
                    ->values()
                    ->toArray();

                $destination['booths'] = $booths
                    ->map(fn($b) => [
                        'booth_id'  => (int) $b->booth_id,
                        'name'      => optional($b->booth)->name,
                        'cost'      => optional($b->booth)->cost ?? 0,
                        'direction' => $b->direction,
                    ])
                    ->values()
                    ->toArray();
            }

            return $destination;
        })->toArray();
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $cost = Cost::with(['service', 'service.approvals', 'service.state'])->where('service_id', $id)->first();

        if (!$cost) {


            /*$total = Container::join('destinations', 'containers.place_id', '=', 'destinations.place_id')
                ->join('booths', 'destinations.booth_id', '=', 'booths.id')
                ->join('services', 'containers.service_id', '=', 'services.id')
                ->where('containers.service_id', $id)
                ->where('destinations.client_id', '=', DB::raw('services.client_id'))
                ->sum('booths.cost');*/


            $cost = new Cost();
            $cost->service_id = $id;
            $cost->waybill = '';
            $cost->booth_costs = 0;
            $cost->travel_cost = 0;
            $cost->save();
            
            // Cargar el servicio después de crear el cost
            $cost->load(['service', 'service.approvals', 'service.state']);
        }

        // Autorellenar casetas desde la plantilla de destinos de cliente
        $autofilled = $this->autofillBoothsFromTemplate($cost);

        $response = $cost->toArray();
        if ($autofilled !== null) {
            $response['formatted_destinations'] = $autofilled;
        }

        return response()->json($response);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        
        // Obtener el servicio para validar el state_id
        $service = Service::find($data["service_id"]);
        
        if (!$service) {
            return response()->json(['error' => 'Servicio no encontrado'], 404);
        }

        // Construir reglas de validación dinámicamente
        $rules = [
            'service_id' => 'required',
            'waybill' => 'nullable|unique:costs,waybill,' . $id
        ];

        $messages = [
            'waybill.unique' => 'La carta porte ya está registrada en otro servicio',
            'travel_cost.required' => 'El precio del viaje es requerido',
            'travel_cost.numeric' => 'El precio del viaje debe ser un número',
            'travel_cost.min' => 'El precio del viaje debe ser mayor a 0',
            'formatted_initial_costs.required' => 'Debe agregar al menos un gasto inicial del viaje',
            'formatted_initial_costs.min' => 'Debe agregar al menos un gasto inicial del viaje',
            'formatted_initial_costs.*.concept.required' => 'El concepto del gasto es requerido',
            'formatted_initial_costs.*.cost.required' => 'El costo es requerido',
            'formatted_initial_costs.*.cost.numeric' => 'El costo debe ser un número',
            'formatted_initial_costs.*.cost.min' => 'El costo debe ser mayor o igual a 0'
        ];

        // Validaciones requeridas en estado 1 (En Espera) y estado 2 (Programado)
        if ($service->state_id == 1 || $service->state_id == 2) {
            $rules['travel_cost'] = 'required|numeric|decimal:0,2|min:0.01';
            $rules['formatted_initial_costs'] = 'required|array|min:1';
            $rules['formatted_initial_costs.*.concept'] = 'required|string';
            $rules['formatted_initial_costs.*.cost'] = 'required|numeric|decimal:0,2|min:0';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        // Procesar gastos iniciales en estado 1 (En Espera) o estado 2 (Programado)
        if ($service->state_id == 1 || $service->state_id == 2) {
            // Registrar quién rellenó los gastos iniciales (solo la primera vez)
            if (is_null($service->initial_expenses_filled_by)) {
                $service->initial_expenses_filled_by = auth()->id();
                $service->save();
            }

            $total_inital_cost = 0;

            Expense::where('service_id', $data["service_id"])->where('type', 'INIT')->delete();

            foreach ($data['formatted_initial_costs'] as $cost) {
                Expense::create([
                    'service_id' => $data["service_id"],
                    'type'       => 'INIT',
                    'concept'    => $cost['concept'],
                    'cost'       => $cost['cost'],
                ]);
                $total_inital_cost += $cost['cost'];
            }

            $containers = Container::where('zombie', 0)->where('service_id', $data["service_id"])->get();

            foreach ($containers as $container) {
                $container->destinations()->delete();

                foreach ($data['formatted_destinations'] as $destination) {
                    if ($destination['container_id'] == $container->id) {
                        if (isset($destination['booths_outbound'])) {
                            foreach ($destination['booths_outbound'] as $booth) {
                                $container->destinations()->create([
                                    'container_id' => $container->id,
                                    'booth_id'     => $booth['booth_id'],
                                    'direction'    => 'outbound'
                                ]);
                            }
                        }
                        if (isset($destination['booths_return'])) {
                            foreach ($destination['booths_return'] as $booth) {
                                $container->destinations()->create([
                                    'container_id' => $container->id,
                                    'booth_id'     => $booth['booth_id'],
                                    'direction'    => 'return'
                                ]);
                            }
                        }
                        // Compatibilidad con estructura antigua
                        if (isset($destination['booths']) && !isset($destination['booths_outbound']) && !isset($destination['booths_return'])) {
                            foreach ($destination['booths'] as $booth) {
                                $container->destinations()->create([
                                    'container_id' => $container->id,
                                    'booth_id'     => $booth['booth_id'],
                                    'direction'    => 'outbound'
                                ]);
                            }
                        }
                    }
                }
            }

            $total = Container::join('destinations', 'containers.id', '=', 'destinations.container_id')
                ->join('booths', 'destinations.booth_id', '=', 'booths.id')
                ->where('service_id', '=', $data["service_id"])
                ->sum('booths.cost');

            $data["booth_costs"] = $total;

            // Solicitar aprobación siempre que no se haya enviado antes para este viaje,
            // sin importar el estado ni si el operador principal está asignado
            if (!$service->hasApprovalPendingOrApproved('initial_expenses')) {
                $requestedBy = $service->initial_expenses_filled_by ?? auth()->id();

                $service->requestApproval(
                    kind: 'initial_expenses',
                    userId: $requestedBy,
                    snapshot: $service->snapshotForInitialExpenses($total),
                    meta: ['total' => $total + $total_inital_cost],
                    scopeId: $service->id
                );

                NotificationHelper::notifyAdmins(
                    'Nueva solicitud de Gastos iniciales',
                    'Se requiere de su aprobación ('. $service->folio .')'
                );
            }
        }

        // Actualizar el cost (todos los campos en state 1 y 2, solo waybill en state > 2)
        if ($service->state_id > 2) {
            Cost::where('id', $id)->first()->update(['waybill' => $data['waybill']]);
        } else {
            Cost::where('id', $id)->first()->update($data);
        }
    
        return response()->json(null, 200);
    }

}