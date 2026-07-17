<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use App\Models\Catalog;
use App\Models\Container;
use App\Models\Historical;
use App\Models\Operator;
use App\Models\Unit;
use App\Models\Substate;
use App\Models\SubstateHistory;
use App\Models\Diesel;
use App\Models\DieselCost;
use App\Models\TreasuryPayment;
use App\Models\Booth;
use App\Models\Expense;
use App\Models\ClientPlace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\NotificationHelper;
use App\Models\ServiceOperator;
use App\Models\ServiceOperatorType;
use App\Models\ServiceOperatorTypeSubstate;
use App\Models\ServiceOperatorTypeRate;



class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @permission services.view
     * @gate view-all-services (optional - filtra servicios por operador)
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       
        $user = auth()->user();
        $startUtc = null;
        $endUtc = null;

        // Calcular rango de fechas si start_date y end_date están presentes
        if (request()->has('start_date') && request()->has('end_date')) {
            $startDate = Carbon::parse(request('start_date'), 'America/Mexico_City')->startOfDay();
            $endDate = Carbon::parse(request('end_date'), 'America/Mexico_City')->endOfDay();
            
            // Convertir a UTC antes del query
            $startUtc = $startDate->copy()->timezone('UTC');
            $endUtc = $endDate->copy()->timezone('UTC');
        }

        // Gate: Si el usuario NO puede ver todos los servicios (ej: Chofer)
        // entonces filtramos solo los servicios asignados a él
        if (Gate::denies('view-all-services')) {

             /*return Service::with(['client','operator', 'unit', 'state','substate', 'containers.place', 'cost'])
                            ->where('zombie', 0)
                            ->where('operator_id', $user->id)
                            ->where('state_id', '>', 1)
                            ->orderBy('id','desc')
                            ->get(); */
            

                $query = Service::with(['client','operator','unit','state','substate','containers.place','cost','evidences'])
                    ->where('zombie', 0)
                    ->where('state_id', '>', 1)
                    ->where('state_id', '<', 5)
                    ->where(function ($q) use ($user) {
                        $q->whereRaw(
                            "(
                                legacy = 0
                                AND EXISTS (
                                    SELECT 1 FROM service_operators so
                                    INNER JOIN service_operator_type_substates sots
                                        ON sots.service_operator_type_id = so.service_operator_type_id
                                        AND sots.substate_id = services.substate_id
                                    WHERE so.service_id = services.id
                                        AND so.operator_id = ?
                                        AND so.zombie = 0
                                )
                            ) OR (
                                legacy = 1
                                AND (
                                    (type_operation IN (1,3) AND (
                                        (substate_id = 7 AND aux_operator_id = ?)
                                        OR (substate_id <> 7 AND operator_id = ?)
                                    ))
                                    OR (type_operation = 2 AND (
                                        (substate_id IN (0) AND aux_operator_id = ?)
                                        OR (substate_id = 17 AND aux2_operator_id = ?)
                                        OR (substate_id NOT IN (0,17) AND operator_id = ?)
                                    ))
                                )
                            )",
                            [$user->id, $user->id, $user->id, $user->id, $user->id, $user->id]
                        );
                    });
                
                // Aplicar filtro de fechas si está presente
                if ($startUtc && $endUtc) {
                    $query->whereBetween('dispatch_date', [$startUtc, $endUtc]);
                }

                return $query->orderBy('id','desc')->get();

        } else {

            /*
            ->where('zombie', 0)
            ->where('state_id', $estado)              
            */

            $query = Service::with(['client','operator', 'unit', 'state','substate', 'containers.place', 'cost', 'approvals', 'evidences'])
                            ->where(function($q) {
                                $q->where('zombie', 0);
                                $estado = 1;
                                if (request()->has('estado') && !empty(request('estado'))) {
                                    $estado = request('estado');
                                    
                                    // Si contiene comas, es un filtro múltiple
                                    if (str_contains($estado, ',')) {
                                        $estados = explode(',', $estado);
                                        $q->whereIn('state_id', $estados);
                                    } else {
                                        $q->where('state_id', $estado);
                                    }
                                } else {
                                    // Por defecto mostrar estado 1 (Solicitado)
                                    $q->where('state_id', $estado);
                                }
                                if (request()->has('operation') && !empty(request('operation'))) {
                                    $q->where('type_operation', request('operation'));
                                }
                            });
            
            // Aplicar filtro de fechas si está presente
            if ($startUtc && $endUtc) {
                $query->whereBetween('dispatch_date', [$startUtc, $endUtc]);
            }
            
            return $query->orderBy('id','desc')->get();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\ServiceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ServiceRequest $request)
    {
        $data = $request->validated();
        $data['diesel_cost_id'] = DieselCost::latest()->first()->id;
        $data['legacy'] = 0; // Nuevos servicios usan la nueva estructura

        $service = Service::create($data);

        
        if (!empty($data['containers'])) {
            foreach ($data['containers'] as $container) {
                $service->containers()->create($container);

                if(isset($container['container_number']) && $container['container_number'])
                    Catalog::firstOrCreate(
                        ['type_collection' => 'container-numbers', 'title' => strtoupper($container['container_number'])]
                    );

                if(isset($container['destine']) && $container['destine'])
                    Catalog::firstOrCreate(
                        ['type_collection' => 'destines', 'title' => strtoupper($container['destine'])]
                    );
            }
        }
        

        if(isset($data['terminal']) && $data['terminal'])
            Catalog::firstOrCreate(
                ['type_collection' => 'terminals', 'title' => strtoupper($data['terminal'])]
            );

        Historical::create(
            ['type' => 'STATUS', 'service_id' => $service->id, 'date' => date('Y-m-d'), 'first_details' => 'En Espera']
        );

        return response()->json($service, 200);
        //return response()->json($service->load('containers'), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $service = Service::with([
            'client', 'operator', 'unit', 'state', 'substate', 'containers.place',
            'cost', 'auxOperator', 'auxUnit', 'aux2Operator', 'aux2Unit', 'approvals',
            'serviceOperators.operator', 'serviceOperators.unit',
            'serviceOperators.type', 'serviceOperators.rate',
            'extra_diesel',
        ])->find($id);

        if (!$service) {
            return response()->json(['error' => 'Servicio no encontrado'], 404);
        }

        // Si es legacy, construir serviceOperators virtuales desde columnas antiguas
        // para que el frontend siempre reciba el mismo contrato
        if ($service->legacy) {
            $types = ServiceOperatorType::where('type_operation', $service->type_operation)
                ->where('zombie', 0)->orderBy('id')->get();

            $virtual = collect();
            foreach ($types as $type) {
                [$opId, $unitId, $opRel, $unitRel] = match ($type->code) {
                    'MAIN'       => [$service->operator_id,      $service->unit_id,      $service->operator,    $service->unit],
                    'AUX_PATIO',
                    'AUX_INICIO' => [$service->aux_operator_id,  $service->aux_unit_id,  $service->auxOperator, $service->auxUnit],
                    'AUX_FIN'    => [$service->aux2_operator_id, $service->aux2_unit_id, $service->aux2Operator,$service->aux2Unit],
                    default      => [null, null, null, null],
                };

                if (!$opId) continue;

                $vo = new ServiceOperator([
                    'service_id'               => $service->id,
                    'operator_id'              => $opId,
                    'unit_id'                  => $unitId ?? 0,
                    'service_operator_type_id' => $type->id,
                    'amount_bonus'             => 0,
                ]);
                $vo->setRelation('operator', $opRel);
                $vo->setRelation('unit',     $unitRel);
                $vo->setRelation('type',     $type);
                $virtual->push($vo);
            }

            $service->setRelation('serviceOperators', $virtual);
        }

        // Construir historial de diesel extra con el estado de cada aprobación
        $extraDieselApprovals = $service->approvals->where('kind', 'extra_diesel');
        $dieselHistory = $service->extra_diesel->sortByDesc('created_at')->map(function ($diesel) use ($extraDieselApprovals) {
            $approval = $extraDieselApprovals
                ->where('scope_id', $diesel->id)
                ->sortByDesc('created_at')
                ->first();
            return [
                'id'                  => $diesel->id,
                'amount'              => $diesel->amount,
                'description'         => $diesel->description,
                'created_at'          => $diesel->created_at,
                'status'              => $approval?->status,
                'service_operator_id' => $diesel->service_operator_id,
            ];
        })->values();

        $service->setAttribute('diesel_history', $dieselHistory);

        return $service;
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\ServiceRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ServiceRequest $request, $id)
    {
        $user = Auth::user();

        $data = $request->validated();

        $service = Service::find($id);
    
        // Detectar si se está asignando diesel por primera vez
        $dieselChanged = isset($data['diesel']) && 
                         $data['diesel'] > 0 && 
                         ($service->diesel == 0 || is_null($service->diesel));
        
        if ($dieselChanged && is_null($service->initial_diesel_filled_by)) {
            $data['initial_diesel_filled_by'] = auth()->id();
        }

        $service->update($data);

        // Procesar asignación de operadores via nueva estructura
        $hasAssignPermission = $user->hasPermission('services.assign');
        if ($hasAssignPermission && isset($data['operators']) && is_array($data['operators'])) {
            // Flipear a la nueva estructura
            $service->update(['legacy' => 0]);

            foreach ($data['operators'] as $op) {
                // Resolver tipo al inicio para poder aplicar guards de estado
                $type = ServiceOperatorType::find($op['service_operator_type_id']);

                // Auxiliares bloqueados una vez que el viaje pasó de En Destino (state > 4)
                if (!$type->is_main && $service->state_id > 4) {
                    continue;
                }

                // Si viene rate_id, resolver el amount_bonus desde la tarifa
                $amountBonus = 0;
                if (!empty($op['rate_id'])) {
                    $rate = ServiceOperatorTypeRate::find($op['rate_id']);
                    $amountBonus = $rate ? $rate->amount : 0;
                }

                ServiceOperator::updateOrCreate(
                    [
                        'service_id'               => $service->id,
                        'service_operator_type_id' => $op['service_operator_type_id'],
                    ],
                    [
                        'operator_id'  => $op['operator_id'],
                        'unit_id'      => $op['unit_id'] ?? 0,
                        'rate_id'      => $op['rate_id'] ?? null,
                        'amount_bonus' => $amountBonus,
                        'zombie'       => 0,
                    ]
                );

                $operator = Operator::find($op['operator_id']);
                // $type ya fue resuelto al inicio del loop

                $last = Historical::where('service_id', $id)
                    ->where('type', 'OPERATOR')
                    ->orderByDesc('id')->first();

                if (!$last || $last->first_details !== $operator->name) {
                    Historical::create([
                        'type'           => 'OPERATOR',
                        'service_id'     => $service->id,
                        'date'           => date('Y-m-d'),
                        'first_details'  => $operator->name,
                        'second_details' => $type->name,
                    ]);
                }

                // Diesel extra para operadores auxiliares (is_main = 0)
                if (!$type->is_main && !empty($op['diesel']) && $op['diesel'] > 0) {
                    $so = ServiceOperator::where('service_id', $service->id)
                        ->where('service_operator_type_id', $op['service_operator_type_id'])
                        ->where('zombie', 0)
                        ->first();

                    if ($so) {
                        // Buscar el último registro de diesel de este operador auxiliar
                        $existingDiesel = Diesel::where('service_id', $service->id)
                            ->where('service_operator_id', $so->id)
                            ->latest()->first();

                        $approvalStatus = null;
                        if ($existingDiesel) {
                            $dieselApproval = $service->approvals()
                                ->where('kind', 'extra_diesel')
                                ->where('scope_id', $existingDiesel->id)
                                ->latest()->first();
                            $approvalStatus = $dieselApproval?->status;
                        }

                        // Solo crear si no hay solicitud pending/approved (o si fue rechazada)
                        if (!$existingDiesel || $approvalStatus === 'rejected') {
                            $auxDiesel = Diesel::create([
                                'service_id'          => $service->id,
                                'service_operator_id' => $so->id,
                                'amount'              => $op['diesel'],
                                'description'         => 'Diesel auxiliar: ' . $type->name,
                            ]);

                            $service->requestApproval(
                                kind:     'extra_diesel',
                                userId:   auth()->id(),
                                snapshot: $service->snapshotForExtraDiesel(
                                    $op['diesel'],
                                    $type->name . ' - ' . $operator->name
                                ),
                                meta:     [],
                                scopeId:  $auxDiesel->id,
                            );

                            NotificationHelper::notifyAdmins(
                                'Nueva solicitud de Diesel Extra',
                                'Operador auxiliar ' . $type->name . ': ' . $operator->name . ' (' . $service->folio . ')'
                            );
                        }
                    }
                }
            }

            $service->refresh();
        }

        // Determinar si el operador principal está asignado (discriminador legacy)
        if ($service->legacy) {
            $hasMainOperator = $service->operator_id > 0 && $service->unit_id > 0;
        } else {
            $hasMainOperator = $service->serviceOperators()
                ->whereHas('type', fn ($q) => $q->where('is_main', 1))
                ->where('zombie', 0)->exists();
        }

        // Operador asigna diesel y choferes
        if ($service->state_id == 1) {
            // Verificar si se asignó diesel y hay operador/unidad
            if (
                $service->diesel > 0 &&
                $hasMainOperator &&
                !$service->hasApprovalPendingOrApproved('initial_diesel_required')
            ) {
                NotificationHelper::notifyAdmins(
                    'Nueva solicitud de Diesel',
                    'Se requiere de su aprobación ('. $service->folio .')'
                );

                $service->requestApproval(
                    kind: 'initial_diesel_required',
                    userId: $service->initial_diesel_filled_by ?? auth()->id(),
                    snapshot: $service->snapshotForInitialDieselRequired(),
                    meta: [],
                    scopeId: $service->id
                );
            }

            if ($hasMainOperator) {
                // Verificar si debe enviar aprobación de diesel
                if (
                    $service->diesel > 0 && 
                    !isset($data['diesel']) && // No se está asignando diesel en esta misma actualización
                    !$service->hasApprovalPendingOrApproved('initial_diesel_required')
                ) {
                    NotificationHelper::notifyAdmins(
                        'Nueva solicitud de Diesel',
                        'Se requiere de su aprobación ('. $service->folio .')'
                    );

                    $service->requestApproval(
                        kind: 'initial_diesel_required',
                        userId: $service->initial_diesel_filled_by ?? auth()->id(),
                        snapshot: $service->snapshotForInitialDieselRequired(),
                        meta: [],
                        scopeId: $service->id
                    );
                }

                // Los gastos iniciales ahora se capturan en estado Programado (state 2)
                // La aprobacion de initial_expenses se solicita desde CostController
            }
        }

        if($user->hasPermission('services.edit')) {
            $existingIds = $service->containers()->pluck('id')->toArray();
            $newIds = collect($data['containers'])->pluck('id')->filter()->toArray();

            // 1. Eliminar los contenedores que ya no están (solo en En Espera)
            if ($service->state_id == 1) {
                $toDelete = array_diff($existingIds, $newIds);
                $service->containers()->whereIn('id', $toDelete)->delete();
            }

            // 2. Recorrer los nuevos contenedores
            foreach ($data['containers'] as $containerData) {
                if (!empty($containerData['id'])) {
                    // Si tiene ID, es una actualización (permitida hasta state 3)
                    if ($service->state_id <= 3) {
                        $container = $service->containers()->find($containerData['id']);
                        if ($container) {
                            $container->update($containerData);
                        }
                    }
                } else {
                    // Si no tiene ID, es uno nuevo (solo en En Espera)
                    if ($service->state_id == 1) {
                        $service->containers()->create($containerData);
                    }
                }
                
                if(isset($containerData['container_type']) && $containerData['container_type'])
                    Catalog::firstOrCreate(
                        ['type_collection' => 'containers', 'title' => strtoupper($containerData['container_type'])]
                    );

                if(isset($containerData['container_number']) && $containerData['container_number'])
                    Catalog::firstOrCreate(
                        ['type_collection' => 'container-numbers', 'title' => strtoupper($containerData['container_number'])]
                    );
            }
        }
        

        if(isset($data['terminal']) && $data['terminal'])
            Catalog::firstOrCreate(
                ['type_collection' => 'terminals', 'title' => strtoupper($data['terminal'])]
            );

        // Historial de unidad (solo para legacy — para nuevos se registra por operator[] loop)
        if ($service->legacy && isset($data['unit_id']) && $data['unit_id'] > 0) {
            $unit = Unit::find($data['unit_id']);
            $last = Historical::where('service_id', $id)->where('type', 'UNIT')
                ->orderByDesc('id')->first();
            if (!$last || $last->first_details !== $unit->econame) {
                Historical::create([
                    'type' => 'UNIT', 'service_id' => $id,
                    'date' => date('Y-m-d'), 'first_details' => $unit->econame,
                ]);
            }
        }

        return response()->json(null, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Service::find($id)->update(['zombie' => 1]);

        return response()->json(null, 204);
    }

    /**
     * Change status the specified resource to canceled.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        Service::find($id)->update(['state_id' => 6]);

        Historical::create(
            ['type' => 'STATUS', 'service_id' => $id, 'date' => date('Y-m-d'), 'first_details' => 'Cancelado']
        );

        return response()->json(null, 204);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reassign(Request $request, $id)
    {
        $data    = $request->all();
        $payment = $data['payment'] == '1' ? 'SI' : 'NO';
        $service = Service::find($id);

        if ($service->legacy) {
            // Legacy: actualizar columnas planas
            $service->update([
                'operator_id' => $data['operator_id'],
                'unit_id'     => $data['unit_id'],
            ]);
        } else {
            // Nuevo: actualizar service_operators del tipo MAIN
            $mainType = ServiceOperatorType::where('type_operation', $service->type_operation)
                ->where('code', 'MAIN')->first();
            if ($mainType) {
                ServiceOperator::updateOrCreate(
                    ['service_id' => $service->id, 'service_operator_type_id' => $mainType->id],
                    ['operator_id' => $data['operator_id'], 'unit_id' => $data['unit_id'], 'zombie' => 0]
                );
            }
        }

        $unit = Unit::find($data['unit_id']);
        $last = Historical::where('service_id', $id)->where('type', 'UNIT')
            ->orderByDesc('id')->first();
        if (!$last || $last->first_details !== $unit->econame) {
            Historical::create(
                ['type' => 'UNIT', 'service_id' => $id, 'date' => date('Y-m-d'), 'first_details' => $unit->econame]
            );
        }

        $operator = Operator::find($data['operator_id']);
        $last = Historical::where('service_id', $id)->where('type', 'OPERATOR')
            ->orderByDesc('id')->first();
        if (!$last || $last->first_details !== $operator->name) {
            Historical::create(
                ['type' => 'OPERATOR', 'service_id' => $id, 'date' => date('Y-m-d'),
                 'first_details' => $operator->name, 'second_details' => $payment]
            );
        }

        return response()->json(null, 204);
    }

    /**
     * Get historical of service.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function historical($id)
    {
        //$service = Service::find($id);

        return [
            'id' => $id,
            'states' => Historical::where('type','STATUS')->where('service_id', $id)->get(),
            'units' => Historical::where('type','UNIT')->where('service_id', $id)->get(),
            'operators' => Historical::where('type','OPERATOR')->where('service_id', $id)->get()
        ];

    }


    /**
     * Get substate history of service.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function substateHistory($id)
    {
        $service = Service::find($id);
        
        if (!$service) {
            return response()->json(['error' => 'Servicio no encontrado'], 404);
        }

        $history = SubstateHistory::with(['operator', 'substate'])
            ->where('service_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'operator_name' => $item->operator->name ?? 'N/A',
                    'substate_name' => $item->substate->name ?? 'N/A',
                    'date' => Carbon::parse($item->created_at)->format('Y-m-d'),
                    'time' => Carbon::parse($item->created_at)->format('H:i:s'),
                    'created_at' => $item->created_at
                ];
            });

        return response()->json([
            'service_id' => $service->id,
            'folio' => $service->folio,
            'history' => $history
        ]);
    }

    /**
     * Set sub state of service.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function change_substate(Request $request)
    {
        $data = $request->all();
        $service = Service::find($data['service_id']);

        // Validación: "Inicia Flete" (substate 2 y 11) requiere gastos aprobados y carta porte
        if (in_array($data['substate_id'], [2, 11])) {
            $service->load('cost');

            $expensesApproval = $service->approvalOf('initial_expenses');
            $expensesApproved = $expensesApproval && $expensesApproval->status === 'approved';
            $hasWaybill       = !empty($service->cost?->waybill);

            if (!$expensesApproved || !$hasWaybill) {
                $reasons = [];
                if (!$expensesApproved) $reasons[] = 'gastos iniciales aprobados';
                if (!$hasWaybill)       $reasons[] = 'carta porte asignada';

                return response()->json([
                    'message' => 'No se puede iniciar el flete. Falta: ' . implode(' y ', $reasons)
                ], 422);
            }
        }

        $service->substate_id = $data['substate_id'];

        // IMPO / CARGA SUELTA
        // substate 2 (Inicia Flete) ya no dispara En Ruta directamente;
        // la transición ocurre automáticamente al cumplirse ambas condiciones
        // (initial_expenses aprobado + substate >= umbral). Ver checkAndTransitionToEnRuta().

        if($data['substate_id'] == 5)   // Termino descarga → En Destino
            $service->state_id = 4;

        if($data['substate_id'] == 8)   // Entrega de vacío → Terminado
            $service->state_id = 5;

        // EXPO
        // substate 11 (Inicia Flete) ya no dispara En Ruta directamente.

        if($data['substate_id'] == 14)  // Finaliza Carga → En Destino
            $service->state_id = 4;

        if($data['substate_id'] == 18)  // Ingreso de Carga Concluido → Terminado
            $service->state_id = 5;

        $service->update();

        // Evaluar transición automática a En Ruta si se alcanzó el substate umbral
        // y los gastos iniciales ya están aprobados.
        $service->refresh();
        $service->checkAndTransitionToEnRuta();
        $service->refresh();

        $operator = Operator::find(auth()->id());

        $substate = Substate::find($data['substate_id']);

        Historical::create(
            [
                'type' => 'SUB_STATUS', 
                'service_id' => $data['service_id'], 
                'date' => date('Y-m-d'),
                'first_details' => $operator->name,
                'second_details' => $substate->name
            ]
        );

        // Guardar en la nueva tabla substate_history
        SubstateHistory::create([
            'service_id' => $data['service_id'],
            'operator_id' => auth()->id(),
            'substate_id' => $data['substate_id'],
            'state_id' => $service->state_id
        ]);

        // Determinar si hubo pase de estafeta (el operador ya no verá el servicio)
        if ($service->legacy) {
            $operatorChanged = in_array($data['substate_id'], [7, 9]);
        } else {
            $operatorChanged = !ServiceOperatorTypeSubstate::where('substate_id', $data['substate_id'])
                ->whereHas('operatorType.serviceOperators', function ($q) use ($data) {
                    $q->where('service_id', $data['service_id'])
                      ->where('operator_id', auth()->id())
                      ->where('zombie', 0);
                })->exists();
        }

        return response()->json(['operator_changed' => $operatorChanged], 200);

    }

    public function download() {
        $user = auth()->user();
        $startUtc = null;
        $endUtc = null;

        // Calcular rango de fechas si start_date y end_date están presentes
        if (request()->has('start_date') && request()->has('end_date')) {
            $startDate = Carbon::parse(request('start_date'), 'America/Mexico_City')->startOfDay();
            $endDate = Carbon::parse(request('end_date'), 'America/Mexico_City')->endOfDay();
            
            // Convertir a UTC antes del query
            $startUtc = $startDate->copy()->timezone('UTC');
            $endUtc = $endDate->copy()->timezone('UTC');
        }

        // Gate: Si el usuario NO puede ver todos los servicios (ej: Chofer)
        if (Gate::denies('view-all-services')) {
            $query = Service::with(['client','operator','unit','state','substate','containers.place','cost'])
                ->where('zombie', 0)
                ->where('state_id', '>', 1)
                ->where('state_id', '<', 5)
                ->where(function ($q) use ($user) {
                    $q->whereRaw(
                        "(
                            legacy = 0
                            AND EXISTS (
                                SELECT 1 FROM service_operators so
                                INNER JOIN service_operator_type_substates sots
                                    ON sots.service_operator_type_id = so.service_operator_type_id
                                    AND sots.substate_id = services.substate_id
                                WHERE so.service_id = services.id
                                    AND so.operator_id = ?
                                    AND so.zombie = 0
                            )
                        ) OR (
                            legacy = 1
                            AND (
                                (type_operation IN (1,3) AND (
                                    (substate_id = 7 AND aux_operator_id = ?)
                                    OR (substate_id <> 7 AND operator_id = ?)
                                ))
                                OR (type_operation = 2 AND (
                                    (substate_id IN (0) AND aux_operator_id = ?)
                                    OR (substate_id = 17 AND aux2_operator_id = ?)
                                    OR (substate_id NOT IN (0,17) AND operator_id = ?)
                                ))
                            )
                        )",
                        [$user->id, $user->id, $user->id, $user->id, $user->id, $user->id]
                    );
                });
            
            // Aplicar filtro de fechas si está presente
            if ($startUtc && $endUtc) {
                $query->whereBetween('dispatch_date', [$startUtc, $endUtc]);
            }
            
            $services = $query->orderBy('id','desc')->get();
        } else {
            $query = Service::with(['client','operator','unit','state','substate','containers.place', 'cost', 'auxOperator', 'auxUnit', 'aux2Operator', 'aux2Unit'])
                ->where(function($q) {
                    $q->where('zombie', 0);
                    $estado = 1;
                    if (request()->has('estado') && !empty(request('estado'))) {
                        $estado = request('estado');
                        
                        // Si contiene comas, es un filtro múltiple
                        if (str_contains($estado, ',')) {
                            $estados = explode(',', $estado);
                            $q->whereIn('state_id', $estados);
                        } else {
                            $q->where('state_id', $estado);
                        }
                    } else {
                        // Por defecto mostrar estado 1 (Solicitado)
                        $q->where('state_id', $estado);
                    }
                    if (request()->has('operation') && !empty(request('operation'))) {
                        $q->where('type_operation', request('operation'));
                    }
                });
            
            // Aplicar filtro de fechas si está presente
            if ($startUtc && $endUtc) {
                $query->whereBetween('dispatch_date', [$startUtc, $endUtc]);
            }
            
            $services = $query->orderBy('id','desc')->get();
        }


        $data = [];
        foreach($services as $service) {
            
            $containers = $service->containers
                            ->pluck('order_number')   
                            ->filter()                
                            ->implode(', ');

            $destines = $service->containers
                                ->pluck('place.name')   
                                ->filter()                
                                ->implode(', ');

            $data[] = [
                'id' => $service->id,
                'dispatch_date' => $service->formatted_dispatch_date,
                'delivery_date' => $service->formatted_delivery_date,
                'imo' => $service->formatted_imo,
                'type_unit' => $service->formatted_unit,
                'unit' => isset($service->unit)? $service->unit->econame : '',
                'containers' => $containers,
                'client' => $service->client->name,
                'destines' => $destines,
                'waybill' => isset($service->cost)? $service->cost->waybill : 0
            ];
        }

        return response()->json($data, 200);
        
    }


    public function request_diesel(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|decimal:0,2|min:0',
            'description' => 'required|string|max:255',
        ]);

        $service = Service::find($id);

        $hasPending = $service->approvals()
            ->where('kind', 'extra_diesel')
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'message' => 'Ya existe una solicitud de diesel extra pendiente para este servicio'
            ], 422);
        }

        $diesel = new Diesel();
        $diesel->amount = $validated['amount'];
        $diesel->description = $validated['description'];
        $diesel->service_id = $id;
        $diesel->save();

        $service->requestApproval(
            kind: 'extra_diesel',
            userId: auth()->id(),
            snapshot: $service->snapshotForExtraDiesel($validated['amount'], $validated['description']),
            meta: [],
            scopeId: $diesel->id
        );

        NotificationHelper::notifyAdmins(
            'Nueva solitud de Diesel extra',
            'Se requiere de su aprobación ('. $service->folio .')'
        );

        return response()->json([
            'message' => 'Diesel registrado correctamente',
            'data' => $diesel
        ], 201);
    }

    public function request_booth(Request $request, $id)
    {
        $validated = $request->validate([
            'booth_id' => 'required|exists:booths,id',
        ]);

        $booth = Booth::find($validated['booth_id']);

        if (!$booth) {
            return response()->json([
                'message' => 'Caseta no encontrada'
            ], 404);
        }

        $expense = Expense::create([
            'service_id' => $id,
            'type' => 'EXTRAS',
            'concept' => 'CASETA EXTRA: ' . $booth->name,
            'cost' => $booth->cost,
        ]);

        $service = Service::find($id);

        $service->requestApproval(
            kind: 'extra_booth',
            userId: auth()->id(),
            snapshot: $service->snapshotForExtraBooth($booth),
            meta: [
                'booth_id' => $booth->id,
                'booth_name' => $booth->name,
                'booth_cost' => $booth->cost
            ],
            scopeId: $service->id
        );

        NotificationHelper::notifyAdmins(
            'Nueva solitud de Caseta extra',
            'Se requiere de su aprobación ('. $service->folio .')'
        );

        return response()->json([
            'message' => 'Caseta extra registrada correctamente',
            'data' => $expense
        ], 201);
    }

    public function weekly_payments()
    {
        // Nuevos (legacy=0): desde service_operators con is_main=1
        $newServices = ServiceOperator::with('operator')
            ->where('zombie', 0)
            ->whereHas('type', fn ($q) => $q->where('is_main', 1))
            ->whereHas('service', fn ($q) => $q->where('state_id', 5)->where('order_paid', 0)->where('zombie', 0)->where('legacy', 0))
            ->select('operator_id', DB::raw('COUNT(*) as total_services'))
            ->groupBy('operator_id')
            ->get()
            ->keyBy('operator_id');

        // Legacy (legacy=1): desde services.operator_id directamente
        $legacyServices = Service::query()
            ->select('operator_id', DB::raw('COUNT(*) as total_services'))
            ->where('state_id', 5)->where('order_paid', 0)->where('zombie', 0)->where('legacy', 1)
            ->groupBy('operator_id')
            ->with('operator')
            ->get()
            ->keyBy('operator_id');

        // Operadores con bonos pendientes (is_main=0, bonus_paid=0, amount_bonus > 0)
        $bonusOperators = ServiceOperator::with('operator')
            ->where('zombie', 0)
            ->where('bonus_paid', 0)
            ->where('amount_bonus', '>', 0)
            ->whereHas('type', fn ($q) => $q->where('is_main', 0))
            ->whereHas('service', fn ($q) => $q->where('state_id', 5)->where('zombie', 0))
            ->select('operator_id', DB::raw('COUNT(*) as total_bonuses'))
            ->groupBy('operator_id')
            ->get()
            ->keyBy('operator_id');

        // Fusionar todos los grupos
        $allOperatorIds = $newServices->keys()
            ->merge($legacyServices->keys())
            ->merge($bonusOperators->keys())
            ->unique();

        $results = $allOperatorIds->map(function ($opId) use ($newServices, $legacyServices, $bonusOperators) {
            $total    = ($newServices[$opId]->total_services ?? 0) + ($legacyServices[$opId]->total_services ?? 0);
            $operator = $newServices[$opId]->operator
                     ?? $legacyServices[$opId]->operator
                     ?? $bonusOperators[$opId]->operator;
            return [
                'operator_id'    => $opId,
                'operator'       => $operator->name ?? 'N/A',
                'total_services' => $total,
                'total_bonuses'  => $bonusOperators[$opId]->total_bonuses ?? 0,
                'payment_date'   => 'Todos los pendientes',
            ];
        })->values();

        return $results;
    }

    public function weekly_operator_payments(Request $request, $id)
    {
        $mapService = function ($item) {
            return [
                'id'             => $item->id,
                'folio'          => $item->folio,
                'waybill'        => $item->cost->waybill ?? '',
                'type_operation' => $item->type_operation,
                'client'         => $item->client->name,
                'delivery_date'  => $item->delivery_date,
                'operator'       => $item->operator->name ?? '',
                'destines'       => $item->containers->map(fn ($c) => ['name' => $c->place->name]),
                'amount'         => ClientPlace::where('client_id', $item->client_id)
                    ->whereIn('place_id', $item->containers->pluck('place_id')->unique()->toArray())
                    ->where('type_unit_id', $item->type_unit)
                    ->where('zombie', 0)
                    ->sum('amount'),
                'payment_date'   => 'Todos los pendientes',
            ];
        };

        // Nuevos (legacy=0): desde service_operators con is_main=1
        $newResults = ServiceOperator::with(['service.operator','service.client','service.containers.place','service.cost'])
            ->where('operator_id', $id)
            ->where('zombie', 0)
            ->whereHas('type', fn ($q) => $q->where('is_main', 1))
            ->whereHas('service', fn ($q) => $q->where('state_id', 5)->where('order_paid', 0)->where('zombie', 0)->where('legacy', 0))
            ->get()
            ->pluck('service');

        // Legacy (legacy=1): desde services.operator_id directamente
        $legacyResults = Service::query()
            ->where('operator_id', $id)->where('state_id', 5)->where('order_paid', 0)->where('zombie', 0)->where('legacy', 1)
            ->orderBy('delivery_date', 'desc')
            ->with(['operator','client','containers.place','cost'])
            ->get();

        $results = $newResults->merge($legacyResults)
            ->sortByDesc('delivery_date')
            ->map($mapService)
            ->values();

        // Bonos pendientes: viajes donde fue operador aux con tarifa pendiente
        $bonuses = ServiceOperator::with(['service.client', 'service.containers.place', 'service.cost', 'type'])
            ->where('operator_id', $id)
            ->where('zombie', 0)
            ->where('bonus_paid', 0)
            ->where('amount_bonus', '>', 0)
            ->whereHas('type', fn ($q) => $q->where('is_main', 0))
            ->whereHas('service', fn ($q) => $q->where('state_id', 5)->where('zombie', 0))
            ->get()
            ->map(fn ($so) => [
                'service_operator_id' => $so->id,
                'id'                  => $so->service->id,
                'folio'               => $so->service->folio,
                'waybill'             => $so->service->cost->waybill ?? '',
                'type_operation'      => $so->service->type_operation,
                'operator_role'       => $so->type->name,
                'client'              => $so->service->client->name,
                'delivery_date'       => $so->service->delivery_date,
                'destines'            => $so->service->containers->map(fn ($c) => ['name' => $c->place->name]),
                'amount'              => $so->amount_bonus,
            ])
            ->values();

        return response()->json([
            'services'  => $results,
            'bonuses'   => $bonuses,
            'discounts' => [],
        ], 200);
    }

    public function save_weekly_operator_payments(Request $request, $id)
    {
        $data = $request->all();

        $newFolio = strtoupper('TAG') . now()->format('YmdHis');

        $totalServices = collect($data['services'])->sum('amount');
        $totalBonuses  = collect($data['bonuses'] ?? [])->sum('amount');
        $totalDiscounts = collect($data['discounts'])->sum('amount');

        $payment = TreasuryPayment::create([
            'folio'      => $newFolio,
            'user_id'    => auth()->id(),
            'operator_id'=> $id,
            'order_date' => now()->format('Y-m-d'),
            'total'      => $totalServices + $totalBonuses - $totalDiscounts,
        ]);

        // Pagos por viajes de flete (operador principal)
        foreach ($data['services'] as $service) {
            // Resolver tipo de operador: buscar registro is_main=1 para este operador+servicio
            $so = ServiceOperator::where('service_id', $service['id'])
                ->where('operator_id', $id)
                ->where('zombie', 0)
                ->whereHas('type', fn($q) => $q->where('is_main', 1))
                ->first();

            if ($so) {
                $typeId = $so->service_operator_type_id;
            } else {
                // Fallback para servicios legacy: tipo principal según type_operation
                $svc = Service::find($service['id']);
                $mainType = ServiceOperatorType::where('type_operation', $svc->type_operation)
                    ->where('is_main', 1)
                    ->where('zombie', 0)
                    ->first();
                $typeId = $mainType?->id;
            }

            $payment->payments()->create([
                'treasury_payment_id'      => $payment->id,
                'service_id'               => $service['id'],
                'service_operator_type_id' => $typeId ?? null,
                'total'                    => $service['amount'],
            ]);
            Service::find($service['id'])->update(['order_paid' => 1]);
        }

        // Pagos por bonos (operadores auxiliares)
        foreach ($data['bonuses'] ?? [] as $bonus) {
            $bonusSo = ServiceOperator::find($bonus['service_operator_id']);

            $payment->payments()->create([
                'treasury_payment_id'      => $payment->id,
                'service_id'               => $bonus['id'],
                'service_operator_type_id' => $bonusSo?->service_operator_type_id,
                'total'                    => $bonus['amount'],
            ]);
            // Marcar el bono como pagado en service_operators
            ServiceOperator::where('id', $bonus['service_operator_id'])
                ->update(['bonus_paid' => 1]);
        }

        foreach ($data['discounts'] as $discount) {
            $payment->discounts()->create([
                'treasury_payment_id' => $payment->id,
                'title'               => $discount['discount'],
                'total'               => $discount['amount'],
            ]);
        }

        /*
            $existingIds = $service->containers()->pluck('id')->toArray();
            $newIds = collect($data['containers'])->pluck('id')->filter()->toArray();

            // 1. Eliminar los contenedores que ya no están
            $toDelete = array_diff($existingIds, $newIds);
            $service->containers()->whereIn('id', $toDelete)->delete();

            // 2. Recorrer los nuevos contenedores
            foreach ($data['containers'] as $containerData) {
                if (!empty($containerData['id'])) {
                    // Si tiene ID, es una actualización
                    $container = $service->containers()->find($containerData['id']);
                    if ($container) {
                        $container->update($containerData);
                    }
                } else {
                    // Si no tiene ID, es uno nuevo
                    $service->containers()->create($containerData);
                }
                
                
            } 
        */


        return response()->json($payment, 200);

    }

    /**
     * Get authorized expenses for a service (for drivers/operators)
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function authorized_expenses($id)
    {
        $service = Service::with(['cost', 'containers.destinations.booth'])->find($id);
        
        if (!$service || !$service->cost) {
            return response()->json(['error' => 'No hay gastos disponibles'], 404);
        }
        
        // Obtener gastos iniciales (formatted_initial_costs)
        $expenses = collect($service->cost->formatted_initial_costs ?? []);
        
        // Obtener casetas de todos los contenedores
        $booths = collect();
        foreach ($service->containers as $container) {
            foreach ($container->destinations as $destination) {
                if ($destination->booth) {
                    $booths->push([
                        'concept' => $destination->booth->name . ' (' . ($destination->direction === 'return' ? 'Regreso' : 'Ida') . ')',
                        'cost' => $destination->booth->cost
                    ]);
                }
            }
        }
        
        // Mezclar gastos iniciales con casetas
        $allExpenses = $expenses->concat($booths);
        
        // Calcular total
        $total_authorized = $allExpenses->sum('cost');
        
        return response()->json([
            'expenses' => $allExpenses,
            'total_authorized' => $total_authorized,
            'folio' => $service->folio
        ]);
    }

    function dashboard(Request $request) {
        $clientId   = $request->input('client_id');
        $operatorId = $request->input('operator_id');
        $unitId     = $request->input('unit_id');

        $startUtc = null;
        $endUtc = null;

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'), 'America/Mexico_City')->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'), 'America/Mexico_City')->endOfDay();
            $startUtc = $startDate->copy()->timezone('UTC');
            $endUtc = $endDate->copy()->timezone('UTC');
        }

        // Cargar servicios de la semana con relaciones necesarias
        $query = Service::with(['expenses', 'containers.destinations.booth'])
            ->where('state_id', 5)
            ->where('zombie', 0);

        if ($startUtc && $endUtc) {
            $query->whereBetween('delivery_date', [$startUtc, $endUtc]);
        }

        if ($clientId) $query->where('client_id', $clientId);
        if ($operatorId) {
            $query->where(function ($q) use ($operatorId) {
                $q->where(function ($q2) use ($operatorId) {
                    $q2->where('legacy', 1)->where('operator_id', $operatorId);
                })->orWhere(function ($q2) use ($operatorId) {
                    $q2->where('legacy', 0)
                       ->whereHas('serviceOperators', function ($q3) use ($operatorId) {
                           $q3->where('operator_id', $operatorId)->where('zombie', 0)
                              ->whereHas('type', fn ($q4) => $q4->where('is_main', 1));
                       });
                });
            });
        }
        if ($unitId) $query->where('unit_id', $unitId);

        $services = $query->get();

        // Conteo por día

        $data = $services
                ->groupBy(function ($s) {
                    $date = \Carbon\Carbon::parse($s->delivery_date);
                    return $date->dayOfWeekIso; // 1=Lunes ... 7=Domingo
                })
                ->map(fn($group) => $group->count());

        $daysOrder = [1, 2, 3, 4, 5, 6, 7];
        $dayNames  = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $servicesNumber = [];
        foreach ($daysOrder as $dw) {
            $servicesNumber[] = (int)($data[$dw] ?? 0);
        }

        // Monto total por día
        $amountData = $services
            ->groupBy(function ($s) {
                $date = \Carbon\Carbon::parse($s->delivery_date);
                return $date->dayOfWeekIso;
            })
            ->map(function ($group) {
                return $group->sum(function ($service) {
                    $expenses = $service->expenses->sum(fn($e) => $e->cost ?? 0);
                    $booths = $service->containers->sum(fn($c) =>
                        $c->destinations->sum(fn($d) => $d->booth->cost ?? 0)
                    );
                    return $expenses + $booths;
                });
        });


        $servicesAmount = [];
        foreach ($daysOrder as $dw) {
            $servicesAmount[] = (float)($amountData[$dw] ?? 0);
        }

        return response()->json([
            'labels' => $dayNames,
            'servicesNumber' => $servicesNumber,
            'servicesAmount' => $servicesAmount, 
            'date_range' => [
                'start' => $request->input('start_date'),
                'end' => $request->input('end_date'),
            ]
        ]);
    }


    /*
    function dashboard(Request $request) {
        $clientId   = $request->input('client_id');
        $operatorId = $request->input('operator_id');
        $unitId     = $request->input('unit_id');
        $weekOffset = (int) $request->input('week_offset', 0);

        $today = Carbon::now('America/Mexico_City')->addWeeks($weekOffset)->startOfDay();
        
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek   = $today->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        // Convertir a UTC antes del query
        $startUtc = $startOfWeek->copy()->timezone('UTC');
        $endUtc   = $endOfWeek->copy()->timezone('UTC');

        $query = Service::query()
            ->where('state_id', 5)
            ->where('zombie', 0)
            ->whereBetween('delivery_date', [$startUtc, $endUtc]);

        if ($clientId) $query->where('client_id', $clientId);
        if ($operatorId) $query->where('operator_id', $operatorId);
        if ($unitId) $query->where('unit_id', $unitId);

        $data = $query
            ->selectRaw('DAYOFWEEK(delivery_date) AS dw, COUNT(*) AS total')
            ->groupBy('dw')
            ->get()
            ->pluck('total', 'dw');

        $daysOrder = [2, 3, 4, 5, 6, 7, 1];
        $dayNames  = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $servicesNumber = [];
        foreach ($daysOrder as $dw) {
            $servicesNumber[] = (int)($data[$dw] ?? 0);
        }

        $servicesAmount = [0,0,0,0,0,0,0];

        return response()->json([
            'labels' => $dayNames,
            'servicesNumber' => $servicesNumber,
            'servicesAmount' => $servicesAmount, 
            'week_range' => [
                'start' => $startOfWeek->toDateString(),
                'end' => $endOfWeek->toDateString(),
            ]
        ]);
    }
    */

    function dashboard_services(Request $request) {
        $clientId   = $request->input('client_id');
        $operatorId = $request->input('operator_id');
        $unitId     = $request->input('unit_id');

        $startUtc = null;
        $endUtc = null;

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'), 'America/Mexico_City')->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'), 'America/Mexico_City')->endOfDay();
            $startUtc = $startDate->copy()->timezone('UTC');
            $endUtc = $endDate->copy()->timezone('UTC');
        }

        $query = Service::with(['client', 'unit', 'containers.place', 'containers.destinations', 'cost', 'expenses', 'payment', 'extra_diesel', 'diesel_cost'])
                            ->where('zombie', 0)
                            ->where('state_id', 5);

        if ($startUtc && $endUtc) {
            $query->whereBetween('delivery_date', [$startUtc, $endUtc]);
        }


        if ($clientId) $query->where('client_id', $clientId);
        if ($operatorId) {
            $query->where(function ($q) use ($operatorId) {
                $q->where(function ($q2) use ($operatorId) {
                    $q2->where('legacy', 1)->where('operator_id', $operatorId);
                })->orWhere(function ($q2) use ($operatorId) {
                    $q2->where('legacy', 0)
                       ->whereHas('serviceOperators', function ($q3) use ($operatorId) {
                           $q3->where('operator_id', $operatorId)->where('zombie', 0)
                              ->whereHas('type', fn ($q4) => $q4->where('is_main', 1));
                       });
                });
            });
        }
        if ($unitId) $query->where('unit_id', $unitId);

        $items = $query->orderBy('id','desc')
                        ->get()
                        ->map(function ($item) {

                            $extra_diesel = $item->diesel?->amount ?? 0;

                            $expenses = $item->expenses->sum(fn($e) => $e->cost ?? 0);
                            $payments = $item->payment?->total ?? 0;
                            $diesel = $item->diesel ?? 0;
                            
                            $diesel_cost = ($diesel + $extra_diesel) * $item->diesel_cost->price;
                            $commission = $item->commission;
                            $flete = $item->cost?->travel_cost ?? 0;

                            $booths = $item->containers->sum(fn($c) => 
                                $c->destinations->sum(fn($d) => $d->booth->cost ?? 0)
                            );
                            $utility = $flete - ($commission + $diesel_cost + $payments + $expenses + $booths);
                            $rentability = $flete > 0 ? ($utility / $flete) * 100 : 0;
                            $commission_percent = $flete > 0 ? round(($commission / $flete) * 100, 2) : 0;

                            return [
                                'dispatch_date' => $item->formatted_dispatch_date,
                                'delivery_date' => $item->formatted_delivery_date,
                                'type' => $item->formatted_imo,
                                'unit' => $item->unit?->econame ?? '-',
                                'ref' => $item->containers->pluck('order_number')->implode(', '),
                                'containers' => $item->containers->pluck('container_number')->implode(', '),
                                'client' => $item->client->name ?? '-',
                                'destines' => $item->containers->map(fn($c) => $c->place->name)->implode(', '),
                                'ccp' => $item->cost?->waybill ?? 0,
                                'booths' => $booths,
                                'expenses' => $expenses,
                                'payments' => $payments,
                                'diesel' => $diesel + $extra_diesel,
                                'diesel_cost' => $diesel_cost,
                                'comition' => $commission,
                                'commission_percent' => $commission_percent,
                                'flete' => $flete,
                                'utility' => $utility,
                                'rentability' => $rentability,
                            ];
                        });
        
        // Calcular totales agregados para PIE chart
        $totalBooths = $items->sum('booths');
        $totalExpenses = $items->sum('expenses');
        $totalPayments = $items->sum('payments');
        $totalDieselCost = $items->sum('diesel_cost');
        $totalCommission = $items->sum('comition');
        $totalFlete = $items->sum('flete');
        $totalUtility = $items->sum('utility');
        
        // Calcular porcentajes para el PIE
        $operativeCostsPie = [];
        if ($totalFlete > 0) {
            $operativeCostsPie = [
                'labels' => ['Casetas', 'Gastos', 'Sueldos', 'Diesel', 'Comisión', 'Utilidad'],
                'values' => [
                    round(($totalBooths / $totalFlete) * 100, 2),
                    round(($totalExpenses / $totalFlete) * 100, 2),
                    round(($totalPayments / $totalFlete) * 100, 2),
                    round(($totalDieselCost / $totalFlete) * 100, 2),
                    round(($totalCommission / $totalFlete) * 100, 2),
                    round(($totalUtility / $totalFlete) * 100, 2)
                ],
                'total_flete' => $totalFlete
            ];
        }

        return response()->json([
            'items' => $items, 
            'date_range' => [
                'start' => $request->input('start_date'),
                'end' => $request->input('end_date'),
            ],
            'operativeCostsPie' => $operativeCostsPie
        ]);
    }

    function diesel_costs(Request $request) {
        return DieselCost::where('zombie', 0)->orderBy('id','desc')->get(); 
    }

    function diesel_cost(Request $request) {
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|decimal:0,2|min:0.01'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();
        $data['active'] = 1;

        DieselCost::query()->update(['active' => 0]);

        $cost = DieselCost::create($data);

        return response()->json($cost, 200);
    }

    function save_commission(Request $request) {
        $data = $request->all();
        $service = Service::find($data['service_id']);
        $service->commission = $data['commission'];
        $service->update();
        return response()->json(null, 200);
    }

}

