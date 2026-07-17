<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Maintenance;
use App\Models\TypeMaintenance;
use App\Models\Inventory;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\TreasuryMaintenance;
use App\Models\MaintenanceEvidence;
use Carbon\Carbon;
use App\Helpers\NotificationHelper;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
        
        // Construir query con filtros
        $query = Maintenance::with(['type_maintenance','unit', 'status', 'approvals', 'evidences'])
            ->where(function($q) {
                $q->where('zombie', 0);
                
                if (request()->has('estado') && !empty(request('estado'))) {
                    $estado = request('estado');
                    
                    // Si es "todos", mostrar estados 1,2,3,4
                    if ($estado === 'todos') {
                        $q->whereIn('maintenance_status_id', [1, 2, 3, 4, 5]);
                    }
                    // Si contiene comas, es filtro múltiple
                    elseif (str_contains($estado, ',')) {
                        $estados = explode(',', $estado);
                        $q->whereIn('maintenance_status_id', $estados);
                    }
                    // Estado único
                    else {
                        $q->where('maintenance_status_id', $estado);
                    }
                } else {
                    // Por defecto mostrar estado 1 (Solicitado)
                    $q->where('maintenance_status_id', 1);
                }
            });
        
        // Aplicar filtro de fechas si está presente (AND con estado)
        if ($startUtc && $endUtc) {
            $query->whereBetween('init_date', [$startUtc, $endUtc]);
        }
        
        return $query->orderBy('id','desc')->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required',
            'init_date' => 'required',
            'kms' => 'required',
            'description' => 'required',
            'type_maintenance_id' => 'required'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $newFolio = strtoupper("MTT") . now('America/Mexico_City')->format('YmdHis'); //str_pad(($lastFolio + 1), 10, '0', STR_PAD_LEFT);

        $data['folio'] = $newFolio;

        $maintenance = Maintenance::create($data);

        if (!empty($data['inventory_requests'])) {
            foreach ($data['inventory_requests'] as $inventory_request) {
                $inventoryRequest = $maintenance->inventoryRequests()->create($inventory_request);
                
                //$this->changeInventoryStock($inventoryRequest->inventory_id, $inventory_request['quantity'] * -1);

            }
        }

        if (!empty($data['parts_supplier_requests'])) {
            // Guardar solicitudes
            foreach ($data['parts_supplier_requests'] as $part_supplier_request) {
                $maintenance->partsSupplierRequests()->create($part_supplier_request);
            }
        }
        
        // Calcular totales por proveedor para metadata
        $supplierData = collect($data['parts_supplier_requests'] ?? [])
            ->groupBy('supplier_id')
            ->map(function($items, $supplierId) {
                $supplier = Supplier::find($supplierId);
                $totalProducts = $items->where('request_type', 2)->sum('cost');
                $totalServices = $items->where('request_type', 1)->sum('cost');
                
                return [
                    'supplier_id' => $supplierId,
                    'supplier_name' => $supplier->name ?? 'Desconocido',
                    'products' => $totalProducts,
                    'services' => $totalServices,
                    'total' => $totalProducts + $totalServices
                ];
            });
        
        $maintenance->requestApproval(
            kind: 'maintenance_expenses',
            userId: auth()->id(),
            snapshot: $maintenance->snapshotForMaintenanceExpenses($supplierData),
            meta: [
                'supplier_data' => $supplierData->toArray(),
                'description' => $maintenance->description
            ],
            scopeId: $maintenance->id
        );

        NotificationHelper::notifyAdmins(
            'Nueva solitud de Mantenimiento',
            'Se requiere de su aprobación ('. $maintenance->folio .')'
        );

        return response()->json($maintenance, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Maintenance::with(['type_maintenance','unit', 'status' , 'inventoryRequests.inventory', 'partsSupplierRequests.supplier', 'approvals'])->find($id);
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
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required',
            'init_date' => 'required',
            'kms' => 'required',
            'description' => 'required',
            'type_maintenance_id' => 'required'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $maintenance = Maintenance::find($id);

        $maintenance->update($data);


        //INVENTORY
        

        // 1. Eliminar los que ya no están
        $newIds = collect($data['inventory_requests'])
            ->pluck('id')
            ->filter()
            ->toArray();

        // Traemos solo los registros que ya no están en $newIds
        $toDelete = $maintenance->inventoryRequests()
            ->whereNotIn('id', $newIds)
            ->get();

        // Recorremos y aplicamos la lógica
        foreach ($toDelete as $inventoryItem) {
            $this->changeInventoryStock($inventoryItem->inventory_id, $inventoryItem->quantity);
            $inventoryItem->delete(); // cada modelo se elimina individualmente
        }


        // 2. Recorrer los nuevos elementos
        foreach ($data['inventory_requests'] as $inventory_request_data) {
            if (!empty($inventory_request_data['id'])) {
                // Si tiene ID, es una actualización
                $inventory_request = $maintenance->inventoryRequests()->find($inventory_request_data['id']);
                if ($inventory_request) {

                    if($inventory_request->quantity != $inventory_request_data['quantity'] ||
                       $inventory_request->inventory_id != $inventory_request_data['inventory_id'])
                    {

                        $inventory = Inventory::find($inventory_request->inventory_id);
                        $inventory->quantity = $inventory->quantity + $inventory_request->quantity;
                        $inventory->update();

                        $inventory_request->update($inventory_request_data);

                        //$this->changeInventoryStock($inventory_request->inventory_id, $inventory_request_data['quantity'] * -1);
                    }

                }
            } else {
                // Si no tiene ID, es uno nuevo

                $inventoryRequest = $maintenance->inventoryRequests()->create($inventory_request_data);

                //$this->changeInventoryStock($inventoryRequest->inventory_id, $inventory_request_data['quantity'] * -1);

            }
        }

        //SUPPLIER
        $existingIds = $maintenance->partsSupplierRequests()->pluck('id')->toArray();
        $newIds = collect($data['parts_supplier_requests'])->pluck('id')->filter()->toArray();

        
        // 1. Eliminar los que ya no están
        $toDelete = array_diff($existingIds, $newIds);
        $maintenance->partsSupplierRequests()->whereIn('id', $toDelete)->delete();

        // 2. Recorrer los nuevos elementos
        foreach ($data['parts_supplier_requests'] as $part_supplier_request_data) {
            if (!empty($part_supplier_request_data['id'])) {
                // Si tiene ID, es una actualización
                $part_supplier_request = $maintenance->partsSupplierRequests()->find($part_supplier_request_data['id']);
                if ($part_supplier_request) {
                    $part_supplier_request->update($part_supplier_request_data);
                }
            } else {
                // Si no tiene ID, es uno nuevo
                $maintenance->partsSupplierRequests()->create($part_supplier_request_data);
            }
        }

        // Calcular totales por proveedor para metadata
        $supplierData = collect($data['parts_supplier_requests'])
            ->groupBy('supplier_id')
            ->map(function($items, $supplierId) {
                $supplier = Supplier::find($supplierId);
                $totalProducts = $items->where('request_type', 2)->sum('cost');
                $totalServices = $items->where('request_type', 1)->sum('cost');
                
                return [
                    'supplier_id' => $supplierId,
                    'supplier_name' => $supplier->name ?? 'Desconocido',
                    'products' => $totalProducts,
                    'services' => $totalServices,
                    'total' => $totalProducts + $totalServices
                ];
            });

        $maintenance->requestApproval(
            kind: 'maintenance_expenses',
            userId: auth()->id(),
            snapshot: $maintenance->snapshotForMaintenanceExpenses($supplierData),
            meta: [
                'supplier_data' => $supplierData->toArray(),
                'description' => $maintenance->description
            ],
            scopeId: $maintenance->id
        );

        NotificationHelper::notifyAdmins(
            'Nueva solitud de Mantenimiento',
            'Se requiere de su aprobación ('. $maintenance->folio .')'
        );


        return response()->json(null, 200);
    }

    private function changeInventoryStock($inventory_id, $quantity) 
    {
        $inventory = Inventory::find($inventory_id);
                

        $last = $inventory->quantity;
        $new = $quantity;

        Stock::create([
            'user_id' => auth()->id(),
            'inventory_id' => $inventory->id,
            'last_quantity' => $last,
            'quantity' => $new,
            'new_quantity' => $last + $new,
            'date' => date('Y-m-d')
        ]);

        $inventory->quantity = $last + $new;
        $inventory->update();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json(['message' => 'Mantenimiento no encontrado.'], 404);
        }

        if ($maintenance->maintenance_status_id != 1) {
            return response()->json([
                'message' => 'Solo se puede cancelar un mantenimiento en estado Solicitado.'
            ], 422);
        }

        // Eliminar la aprobación pendiente
        $maintenance->approvals()
            ->where('kind', 'maintenance_expenses')
            ->where('status', 'pending')
            ->delete();

        // Cambiar estado a Cancelado
        $maintenance->update(['maintenance_status_id' => 5]);

        return response()->json(null, 200);
    }

    public function destroy($id)
    {
        Maintenance::find($id)->update(['zombie' => 1]);

        return response()->json(null, 204);
    }

    public function maintenance_types() 
    {
        $types = TypeMaintenance::where('zombie', 0)
            ->orderBy('id', 'desc')
            ->get();
        return response()->json($types, 200);
    }

    /**
     * Set sub state of service.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function change_state(Request $request)
    {
        $data = $request->all();
        $mantto = Maintenance::find($data['mantto_id']);

        // Bloquear transición a estado 4 si no hay evidencias
        if ($data['state_id'] == 4) {
            $evidenceCount = $mantto->evidences()->count();
            if ($evidenceCount === 0) {
                return response()->json([
                    'message' => 'No se puede finalizar el mantenimiento sin evidencias fotográficas.'
                ], 422);
            }
        }

        $mantto->maintenance_status_id = $data['state_id'];

        $mantto->update();

        if($data['state_id'] == 3) {
            $inventory_request = $mantto->inventoryRequests()->where('maintenance_id', $mantto->id)->get();

            foreach($inventory_request as $item) {
                $this->changeInventoryStock($item->inventory_id, $item->quantity * -1);
            }
        }

        return response()->json(null, 204);

    }

    public function uploadEvidence(Request $request, $id)
    {
        $request->validate([
            'photos.*' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $maintenance = Maintenance::findOrFail($id);

        if ($maintenance->maintenance_status_id != 3) {
            return response()->json([
                'message' => 'Solo se pueden cargar evidencias en estado En Proceso'
            ], 400);
        }

        $paths = [];
        foreach ($request->file('photos') as $photo) {
            $path = $photo->store('evidencias_mantenimientos', 'public');
            $filename = basename($path);
            $paths[] = $path;

            MaintenanceEvidence::create([
                'maintenance_id' => $id,
                'file_name' => $filename,
            ]);
        }

        return response()->json([
            'message' => 'Fotos subidas correctamente',
            'paths' => $paths,
        ]);
    }

    function dashboard(Request $request) {
        $unitId = $request->input('unit_id');
        $startUtc = null;
        $endUtc = null;

        // Calcular rango de fechas si start_date y end_date están presentes
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'), 'America/Mexico_City')->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'), 'America/Mexico_City')->endOfDay();
            
            // Convertir a UTC antes del query
            $startUtc = $startDate->copy()->timezone('UTC');
            $endUtc = $endDate->copy()->timezone('UTC');
        }

        $query = Maintenance::query()
            ->where('maintenance_status_id', 4)
            ->where('maintenances.zombie', 0);
        
        // Aplicar filtro de fechas solo si están presentes
        if ($startUtc && $endUtc) {
            $query->whereBetween('init_date', [$startUtc, $endUtc]);
        }

        if ($unitId) $query->where('unit_id', $unitId);

        $daysOrder = [2, 3, 4, 5, 6, 7, 1];
        $dayNames  = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        //Cantidad por día

        $data = $query
            ->selectRaw('DAYOFWEEK(init_date) AS dw, COUNT(*) AS total')
            ->groupBy('dw')
            ->get()
            ->pluck('total', 'dw');

        $servicesNumber = [];
        foreach ($daysOrder as $dw) {
            $servicesNumber[] = (int)($data[$dw] ?? 0);
        }

        //Cantidad por tipo de mantenimiento

        $typesData = $query
            ->join('types_maintenance as tm', 'tm.id', '=', 'maintenances.type_maintenance_id')
            ->selectRaw('tm.name as type_name, COUNT(*) as total')
            ->groupBy('tm.name')
            ->get();

        $typeLabels = $typesData->pluck('type_name');
        $typeTotals = $typesData->pluck('total');

        //Costo por día

        $servicesAmount = [];

        $costsData = $query
        ->join('parts_supplier_requests as psr', 'psr.maintenance_id', '=', 'maintenances.id')
        ->selectRaw('DAYOFWEEK(maintenances.init_date) AS dw, SUM(psr.cost) AS total_cost')
        ->groupBy('dw')
        ->get()
        ->pluck('total_cost', 'dw');

        foreach ($daysOrder as $dw) {
            $servicesAmount[] = (int)($costsData[$dw] ?? 0);
        }

        


        return response()->json([
            'labels' => $dayNames,
            'servicesNumber' => $servicesNumber,
            'servicesAmount' => $servicesAmount, 
            'servicesType' => [
                'labels' => $typeLabels,
                'values' => $typeTotals
            ]
        ]);
    }

    function dashboard_maintenances(Request $request) {
        $unitId = $request->input('unit_id');
        $startUtc = null;
        $endUtc = null;

        // Calcular rango de fechas si start_date y end_date están presentes
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'), 'America/Mexico_City')->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'), 'America/Mexico_City')->endOfDay();
            
            // Convertir a UTC antes del query
            $startUtc = $startDate->copy()->timezone('UTC');
            $endUtc = $endDate->copy()->timezone('UTC');
        }


        $query = Maintenance::with(['partsSupplierRequests','type_maintenance'])
                            ->where('zombie', 0)
                            ->where('maintenance_status_id', 4);
        
        // Aplicar filtro de fechas solo si están presentes
        if ($startUtc && $endUtc) {
            $query->whereBetween('init_date', [$startUtc, $endUtc]);
        }

        if ($unitId) $query->where('unit_id', $unitId);

        $items = $query->orderBy('id','desc')
                        ->get()
                        ->map(function ($item)  use ($dateRange) {
                            return [
                                'init_date' => $item->formatted_init_date,
                                'type' => $item->type_maintenance->name,
                                'unit' => $item->unit ? $item->unit->econame : '-',
                                'description' => $item->description,
                                'kms' => $item->kms,
                                'parts' => $item->partsSupplierRequests
                                                     ->map(fn($req) => $req->description)
                                                     ->implode(', '),
                                'costs' => $item->partsSupplierRequests->sum(function ($part) {
                                    return $part->cost ?? 0;
                                })
                            ];
                        }); 

        return response()->json([
            'items' => $items
        ]);
    }


    public function download() {
        $hasWeekOffset = request()->has('week_offset');
        $startUtc = null;
        $endUtc = null;

        // Calcular rango de semana si week_offset está presente
        if ($hasWeekOffset) {
            $weekOffset = (int) request()->input('week_offset', 0);
            $today = Carbon::now('America/Mexico_City')->addWeeks($weekOffset)->startOfDay();
            
            $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek   = $today->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

            // Convertir a UTC antes del query
            $startUtc = $startOfWeek->copy()->timezone('UTC');
            $endUtc   = $endOfWeek->copy()->timezone('UTC');
        }
        
        // Construir query con filtros
        $query = Maintenance::with(['unit', 'status', 'type_maintenance'])
            ->where(function($q) {
                $q->where('zombie', 0);
                
                if (request()->has('estado') && !empty(request('estado'))) {
                    $estado = request('estado');
                    
                    // Si es "todos", mostrar estados 1,2,3,4
                    if ($estado === 'todos') {
                        $q->whereIn('maintenance_status_id', [1, 2, 3, 4, 5]);
                    }
                    // Si contiene comas, es filtro múltiple
                    elseif (str_contains($estado, ',')) {
                        $estados = explode(',', $estado);
                        $q->whereIn('maintenance_status_id', $estados);
                    }
                    // Estado único
                    else {
                        $q->where('maintenance_status_id', $estado);
                    }
                } else {
                    // Por defecto mostrar estado 1 (Solicitado)
                    $q->where('maintenance_status_id', 1);
                }
            });
        
        // Aplicar filtro de semana si está presente (AND con estado)
        if ($startUtc && $endUtc) {
            $query->whereBetween('init_date', [$startUtc, $endUtc]);
        }
        
        $matts = $query->orderBy('id','desc')->get();


        $data = [];
        foreach($matts as $matt) {
            
            $data[] = [
                'id' => $matt->id,
                'folio' => $matt->folio,
                'type_maintenance' => isset($matt->type_maintenance)? $matt->type_maintenance->name : '',
                'description' => $matt->description,
                'kms' => $matt->kms,
                'init_date' => $matt->init_date,
                'econame' => isset($matt->unit)? $matt->unit->econame : '',
                'name' => isset($matt->status)? $matt->status->name : ''
            ];
        }

        return response()->json($data, 200);
        
    }
}
