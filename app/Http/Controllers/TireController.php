<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Tire;
use App\Models\Inventory;
use App\Helpers\NotificationHelper;

class TireController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Tire::with(['unit', 'inventory'])->where('zombie', 0);

        if (request()->has('q') && !empty(request('q'))) {
            $q = request('q');
            $query->where('serial', 'like', '%' . $q . '%'); 
        }

        return $query->orderBy('id', 'desc')->get();
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
            'unit_id' => 'required|exists:units,id',
            'inventory_id' => [
                'required',
                'exists:inventories,id',
                function ($attribute, $value, $fail) {
                    $inventory = Inventory::find($value);
                    if (!$inventory || $inventory->quantity < 1) {
                        $fail('No hay stock disponible para la llanta seleccionada.');
                    }
                },
            ],
            'serial' => 'required|string|unique:tires,serial',
            'position' => 'required|string',
            'date' => 'required|string'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $tire = Tire::create($data);

        $tire->requestApproval(
            kind: 'tire_expenses',
            userId: auth()->id(),
            snapshot: $tire->snapshotForTireExpenses($tire),
            meta: [],
            scopeId: $tire->id
        );

        NotificationHelper::notifyAdmins(
            'Nueva solitud de Cambio de llanta',
            'Se requiere de su aprobación ('. $tire->id .')'
        );

        return response()->json($tire, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Tire::with(['unit', 'inventory'])->find($id);
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
        $tire = Tire::find($id);
        
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'inventory_id' => [
                'required',
                'exists:inventories,id',
                function ($attribute, $value, $fail) use ($tire) {
                    // Solo validar si cambia el inventory_id
                    if ($tire->inventory_id != $value) {
                        $inventory = Inventory::find($value);
                        if (!$inventory || $inventory->quantity < 1) {
                            $fail('No hay stock disponible para la llanta seleccionada.');
                        }
                    }
                },
            ],
            'serial' => 'required|string|unique:tires,serial,' . $id,
            'position' => 'required|string',
            'date' => 'required|string'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();
        $tire->update($data);

        return response()->json(null, 200);
    }

    /**
     * Logic remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Tire::find($id)->update(['zombie' => 1]);

        return response()->json(null, 204);
    }
}
