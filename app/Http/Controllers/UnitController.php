<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Unit;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Unit::where('zombie', 0);

        if (request()->has('q') && !empty(request('q'))) {
            $q = request('q');
            $query->where('econame', 'like', '%' . $q . '%')->where('type',1); 
        }

        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * Display a listing of all units for maintenance (without type filter).
     *
     * @return \Illuminate\Http\Response
     */
    public function indexForMaintenance()
    {
        $query = Unit::where('zombie', 0);

        if (request()->has('q') && !empty(request('q'))) {
            $q = request('q');
            $query->where('econame', 'like', '%' . $q . '%');
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
            'econame' => 'required',
            'type' => 'required'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $unit = Unit::create($data);

        return response()->json($unit, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Unit::find($id);
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
            'econame' => 'required',
            'type' => 'required'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        Unit::find($id)->update($data);

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
        Unit::find($id)->update(['zombie' => 1]);

        return response()->json(null, 204);
    }
}
