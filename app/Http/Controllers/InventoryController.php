<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Inventory;
use App\Models\Stock;
use App\Helpers\NotificationHelper;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Inventory::with(['stocks'])->where('zombie', 0);

        if (request()->has('q') && !empty(request('q'))) {
            $q = request('q');
            $query->where('name', 'like', '%' . $q . '%'); 
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
            'name' => 'required',
            'presentation' => 'required',
            'brand' => 'required',
            'quantity' => 'required|numeric'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $inventory = Inventory::create($data);

        NotificationHelper::notifyAdmins(
            'Nuevo producto en el inventario',
            'Se agrego un nuevo producto ('. $data["name"] .')'
        );

        return response()->json($inventory, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Inventory::with(['stocks.user'])->find($id);
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
        $rules = [
            'name' => 'required',
            'presentation' => 'required',
            'brand' => 'required',
            'quantity' => 'required|numeric'
        ];

        $data = $request->all();

        if(isset($data['inventory']))
        {
            $rules['inventory'] = 'required|numeric';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }

        $last = $data['quantity'];
        $new =  $data['inventory'];

        $data['quantity'] = $last + $new;

        Inventory::find($id)->update($data);

        NotificationHelper::notifyAdmins(
            'Actualización de stock en el inventario',
            'Se agrego '. $new .' elemento(s) a '. $data["name"]
        );

        Stock::create([
            'user_id' => $data['user_id'],
            'inventory_id' => $id,
            'last_quantity' => $last,
            'quantity' => $new,
            'new_quantity' => $last + $new,
            'date' => date('Y-m-d')
        ]);

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
        Inventory::find($id)->update(['zombie' => 1]);

        return response()->json(null, 204);
    }
}
