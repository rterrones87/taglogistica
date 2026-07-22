<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TreasuryService;
use App\Models\TreasuryMaintenance;
use App\Models\TreasuryPayment;
use App\Models\Expense;
use App\Models\Service;
use App\Models\Evidence;
use App\Models\Payment;
use App\Models\Discount;
use Carbon\Carbon;
use App\Models\ServiceOperator;

class TreasuryController extends Controller
{
    /**
     * Resuelve el nombre del operador principal de un servicio,
     * discriminando por el flag legacy.
     */
    private function resolveMainOperatorName($service): string
    {
        if ($service->legacy) {
            return $service->operator?->name ?? 'No asignado';
        }
        $mainSO = $service->serviceOperators->first(fn ($so) => $so->type?->is_main == 1);
        return $mainSO?->operator?->name ?? 'No asignado';
    }

	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
	public function services(Request $request) {
		$type = $request->query('type') ?? "1";
    	$htype = $request->query('htype');
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

		$query = TreasuryService::with([
									'service.operator',
									'service.serviceOperators.operator',
									'service.serviceOperators.type',
									'user', 'reviewer'
								])
							    ->where('zombie', 0);

		if ($type == 3) {
            if (!empty($htype)) {
            	$query->where('type_payment', $htype)->where('paid', 1);
            } else {
            	$query->where('paid', 1);
        	}
        } else {
            $query->where('type_payment', $type)->where('paid', 0);
        }

		// Aplicar filtro de fechas si está presente
	    if ($startUtc && $endUtc) {
	        $query->whereBetween('order_date', [$startUtc, $endUtc]);
	    }

		return $query->orderBy('id','desc')
			  ->get()
			  ->map(function ($item) {
			  		return [
			  			'id' => $item->id,
			  			'service_id' => $item->service->id,
			  			'folio' => $item->service->folio,
			  			'user' => $item->user->name,
			  			'approver' => $item->reviewer?->name ?? 'N/A',
			  			'order_date' => $item->order_date,
			  			'operator' => $this->resolveMainOperatorName($item->service),
			  			'total' => '$' . number_format($item->total, 2, '.', ','),
			  			'payment_date' => $item->payment_date
			  		];
			  });
	}


	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
	public function maintenances(Request $request) {
		$type = $request->query('type') ?? "1";
    	$htype = $request->query('htype');
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

	$query = TreasuryMaintenance::with(['maintenance.partsSupplierRequests.supplier', 'maintenance.unit', 'user', 'reviewer'])
							    ->where('zombie', 0);

        
        if ($type == 6) {
            if (!empty($htype)) {
            	$query->whereHas('maintenance', function ($q) use ($htype) {
	                $q->where('type_maintenance_id', $htype);
	            })->where('paid', 1);
            } else {
            	$query->where('paid', 1);
        	}
        } else {
            $query->whereHas('maintenance', function ($q) use ($type) {
                $q->where('type_maintenance_id', $type);
            })->where('paid', 0);
        }
	   
	    // Aplicar filtro de fechas si está presente
	    if ($startUtc && $endUtc) {
	        $query->whereBetween('order_date', [$startUtc, $endUtc]);
	    }

	    return $query->orderBy('id', 'desc')
	    	->get()
	        ->map(function ($item) {
	            // Extraer nombre del proveedor desde la descripción
	            $supplier = 'N/A';
	            if (preg_match('/^(.+?) - \d+ productos, \d+ servicios/i', $item->description, $matches)) {
	                $supplier = $matches[1];
	            }
	            
	            return [
	                'id' => $item->id,
	                'order_date' => $item->order_date,
	                'total' =>  '$' . number_format($item->total, 2, '.', ','),
	                'user' => $item->user->name,
	                'approver' => $item->reviewer?->name ?? 'N/A',
	                'folio' => $item->maintenance->folio,
	                'unit' => $item->maintenance->unit->econame ?? 'N/A',
	                'description' => $item->maintenance->description,
	                'supplier' => $supplier,
	                'payment_date' => $item->payment_date
	            ];
	        });
	}

	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
	public function payments(Request $request) {
		$type = $request->query('type') ?? "1";

		$query = TreasuryPayment::with('operator')
								->where('zombie', 0);
		if($type=="2")
			$query->where('paid', 1);
		else 
			$query->where('paid', 0);

		return $query->orderBy('id', 'desc')
	    	->get()
	        ->map(function ($item) {
	            return [
	                'id' => $item->id,
	                'order_date' => $item->order_date,
	                'total' => $item->total,
	                'operator' => $item->operator->name,
	                'folio' => $item->folio,
	                'payment_date' => $item->payment_date
	            ];
	        });
    }

	public function applyPayment(Request $request) {

		$data = $request->all();

		if($data['type'] == 'maintenance') {
			$maintenance = TreasuryMaintenance::find($data['id']);
			$maintenance->paid = 1;
			$maintenance->payment_date = date('Y-m-d');
			$maintenance->update();
		}
		elseif($data['type'] == 'service') {
			$service = TreasuryService::find($data['id']);
			$service->paid = 1;
			$service->payment_date = date('Y-m-d');
			$service->update();
		}
		elseif($data['type'] == 'payment') {
			$payment = TreasuryPayment::find($data['id']);
			$payment->paid = 1;
			$payment->payment_date = date('Y-m-d');
			$payment->update();
		}

		return response()->json(null, 200);

	}

	public function initExpenses($id)
	{
		$item = Service::with('containers.destinations.booth')
						  ->where('id', $id)
						  ->first();
						  
	  	$items = [];
	  	foreach($item->containers as $container) {

	  		foreach($container->destinations as $destination) {
	  			$items[] = [
	  				'number' => $container->container_number,
			  		'place' => $destination->booth->name,
			  		'cost' => $destination->booth->cost,
			  	];
	  		}
	  		
	  	}
						  

		$expenses = Expense::where('service_id', $id)->where('type', 'INIT')->get();
		return response()->json(['expenses' => $expenses, 'costs' => $items], 200);
	}

	public function extExpenses($id)
	{
		$expenses = Expense::where('service_id', $id)->where('type', 'EXTRAS')->get();
		return response()->json(['expenses' => $expenses], 200);
	}

	public function payments_details($id)
	{
		$payments = Payment::with([
							    'service.client',
							    'service.operator',
							    'service.serviceOperators.operator',
							    'service.serviceOperators.type',
							    'service.containers.place',
							    'operatorType',
							])
							->where('treasury_payment_id', $id)
							->get()
							->map(function ($item) {
				                return [
				                    'id' => $item->id,
				                    'folio' => $item->service->folio,
				                    'type_operation' => $item->service->type_operation,
				                    'client' => $item->service->client->name,
				                    'operator' => $this->resolveMainOperatorName($item->service),
				                    'operator_role' => $item->operatorType?->name ?? 'N/A',
				                    'destines' => $item->service->containers->map(function ($req) {
				                            return [
				                                'name' => $req->place->name
				                            ];
				                    }),
				                    'amount' => $item->total
				                ];
				            });
		$discounts = Discount::where('treasury_payment_id', $id)->get();
		return response()->json(['payments' => $payments, 'discounts' => $discounts], 200);
	}

	public function payment_pdfhtml(Request $request, $id)
	{
		
		$query = TreasuryPayment::with([
										'payments.service.containers.place',
										'payments.service.cost',
										'payments.operatorType',
										'discounts',
										'operator'
									])
									->where('id', $id)
									->first();
									return response()->json($query, 200);
							
		return response()->json($payments, 200);							
		
	}

	public function maintenanceDetails($id)
	{
		// Obtener TreasuryMaintenance con todas las relaciones necesarias
		$treasuryMaintenance = TreasuryMaintenance::with([
			'maintenance.unit',
			'maintenance.type_maintenance',
			'maintenance.inventoryRequests.inventory',
			'maintenance.partsSupplierRequests.supplier'
		])->findOrFail($id);

		// Parsear el nombre del proveedor desde la descripción
		$supplierName = null;
		$invoiceRequired = false;
		if (preg_match('/^(.+?) - \d+ productos, \d+ servicios/i', $treasuryMaintenance->description, $matches)) {
			$supplierName = trim($matches[1]);
			// Buscar el proveedor para obtener si requiere factura
			$supplier = $treasuryMaintenance->maintenance->partsSupplierRequests
				->first(fn($r) => $r->supplier && $r->supplier->name === $supplierName)
				?->supplier;
			$invoiceRequired = $supplier?->invoice_required == 1;
		}

		// Filtrar solicitudes del proveedor específico
		$supplierRequests = $treasuryMaintenance->maintenance->partsSupplierRequests
			->filter(function ($request) use ($supplierName) {
				return $request->supplier && $request->supplier->name === $supplierName;
			})
			->map(function ($request) {
				return [
					'id' => $request->id,
					'request_type' => $request->request_type,
					'description' => $request->description,
					'quantity' => $request->quantity,
					'cost' => $request->cost
				];
			})
			->values();

		// Mapear solicitudes de inventario
		$inventoryRequests = $treasuryMaintenance->maintenance->inventoryRequests
			->map(function ($request) {
				return [
					'id' => $request->id,
					'inventory' => [
						'name' => $request->inventory->name
					],
					'quantity' => $request->quantity
				];
			});

		return response()->json([
			'treasury_order' => [
				'id' => $treasuryMaintenance->id,
				'total' => $treasuryMaintenance->total,
				'order_date' => $treasuryMaintenance->order_date,
				'supplier_name' => $supplierName,
				'invoice_required' => $invoiceRequired
			],
			'maintenance' => [
				'folio' => $treasuryMaintenance->maintenance->folio,
				'unit' => $treasuryMaintenance->maintenance->unit->econame ?? 'N/A',
				'type_maintenance' => $treasuryMaintenance->maintenance->type_maintenance->name ?? 'N/A',
				'description' => $treasuryMaintenance->maintenance->description,
				'kms' => $treasuryMaintenance->maintenance->kms
			],
			'inventory_requests' => $inventoryRequests,
			'supplier_requests' => $supplierRequests
		], 200);
	}

	public function upload(Request $request, $id)
	{
	    $request->validate([
	        'photos.*' => 'required|image|mimes:jpg,jpeg,png|max:5120', 
	    ]);

	    $paths = [];
	    foreach ($request->file('photos') as $photo) {
	        $path = $photo->store('evidencias', 'public'); // guarda el archivo
	        $filename = basename($path); // obtiene solo el nombre con extensión

	        $paths[] = $path;

	        Evidence::create([
	            'service_id' => $id,
	            'file_name' => $filename,
	        ]);
	    }

	    return response()->json([
	        'message' => 'Fotos subidas correctamente',
	        'paths' => $paths,
	    ]);
	}

	/**
	 * Download treasury services data for Excel export.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function download(Request $request) {
		$type = $request->query('type') ?? "1";
		$htype = $request->query('htype');
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
	
		$query = TreasuryService::with([
									'service.operator',
									'service.serviceOperators.operator',
									'service.serviceOperators.type',
									'user', 'reviewer'
								])
								->where('zombie', 0);
	
		if ($type == 3) {
			if (!empty($htype)) {
				$query->where('type_payment', $htype)->where('paid', 1);
			} else {
				$query->where('paid', 1);
			}
		} else {
			$query->where('type_payment', $type)->where('paid', 0);
		}
	
		// Aplicar filtro de fechas si está presente
		if ($startUtc && $endUtc) {
			$query->whereBetween('order_date', [$startUtc, $endUtc]);
		}
	
		$data = $query->orderBy('id','desc')
				  ->get()
				  ->map(function ($item) {
				  return [
						  'id' => $item->id,
						  'folio' => $item->service->folio,
						  'user' => $item->user->name,
						  'approver' => $item->reviewer?->name ?? 'N/A',
						  'order_date' => $item->order_date,
					  'operator' => $this->resolveMainOperatorName($item->service),
						  'total' => $item->total,
						  'payment_date' => $item->payment_date
					  ];
				  });
	
		return response()->json($data, 200);
	}

	/**
	 * Download treasury maintenances data for Excel export.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function downloadMaintenances(Request $request) {
		$type = $request->query('type') ?? "1";
		$htype = $request->query('htype');
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
	
	$query = TreasuryMaintenance::with(['maintenance.partsSupplierRequests.supplier', 'maintenance.unit', 'user', 'reviewer'])
								->where('zombie', 0);
	
		if ($type == 6) {
			if (!empty($htype)) {
				$query->whereHas('maintenance', function ($q) use ($htype) {
					$q->where('type_maintenance_id', $htype);
				})->where('paid', 1);
			} else {
				$query->where('paid', 1);
			}
		} else {
			$query->whereHas('maintenance', function ($q) use ($type) {
				$q->where('type_maintenance_id', $type);
			})->where('paid', 0);
		}
	
		// Aplicar filtro de fechas si está presente
		if ($startUtc && $endUtc) {
			$query->whereBetween('order_date', [$startUtc, $endUtc]);
		}
	
		$data = $query->orderBy('id', 'desc')
				  ->get()
				  ->map(function ($item) {
					  // Extraer nombre del proveedor desde la descripción
					  $supplier = 'N/A';
					  if (preg_match('/^(.+?) - \\d+ productos, \\d+ servicios/i', $item->description, $matches)) {
						  $supplier = $matches[1];
					  }

					  // Obtener conceptos del proveedor específico de esta orden
					  $concepts = $item->maintenance->partsSupplierRequests
						  ->filter(fn ($r) => $r->supplier && $r->supplier->name === $supplier)
						  ->pluck('description')
						  ->filter()
						  ->implode(', ');
					  
					  return [
						  'id' => $item->id,
						  'folio' => $item->maintenance->folio,
						  'unit' => $item->maintenance->unit->econame ?? 'N/A',
						  'user' => $item->user->name,
						  'approver' => $item->reviewer?->name ?? 'N/A',
						  'order_date' => $item->order_date,
						  'supplier' => $supplier,
						  'concepts' => $concepts,
						  'total' => $item->total,
						  'description' => $item->maintenance->description,
						  'payment_date' => $item->payment_date
					  ];
				  });
	
		return response()->json($data, 200);
	}


}
//13.221.44.212 qa-ajustador
//54.165.56.96 qa-ajustes

