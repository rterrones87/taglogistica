<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Cost;
use App\Models\Container;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use App\Models\TreasuryService;
use App\Models\Service;
use App\Helpers\NotificationHelper;

class ExtrasController extends Controller
{
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $cost = Cost::with('service', 'service.approvals')->where('service_id', $id)->first();

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
        }

        return $cost;
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
            'service_id' => 'required'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        Expense::where('service_id', $data["service_id"])->where('type', 'EXTRAS')->delete();

        $total = 0;

        foreach ($data['formatted_extras_costs'] as $cost) {
            Expense::create([
                'service_id' => $data["service_id"],
                'type' => 'EXTRAS',
                'concept' => $cost['concept'],
                'cost' => $cost['cost'],
            ]);

            $total += $cost['cost'];
        }

        $service = Service::find($data["service_id"]);

        $service->requestApproval(
            kind: 'extra_expenses',
            userId: auth()->id(),
            snapshot: $service->snapshotForExtraExpenses(),
            meta: [
                'total' => $total
            ],
            scopeId: $service->id
        );

        NotificationHelper::notifyAdmins(
            'Nueva solitud de Gastos extras',
            'Se requiere de su aprobación ('. $service->folio .')'
        );
    
        return response()->json(null, 200);
    }

}