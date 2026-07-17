<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Booth;

class BoothController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Booth::where('zombie', 0);

        if (request()->has('q') && !empty(request('q'))) {
            $q = request('q');
            $query->where('name', 'like', '%' . $q . '%'); 
        }

        return $query->orderBy('id', 'desc')->get();
        //return Booth::where('zombie', 0)->orderBy('id','desc')->get(); 
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
            'cost' => 'required|numeric|decimal:0,2|min:0'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $booth = Booth::create($data);

        return response()->json($booth, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Booth::find($id);
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
            'name' => 'required',
            'cost' => 'required|numeric|decimal:0,2|min:0'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        Booth::find($id)->update($data);

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
        Booth::find($id)->update(['zombie' => 1]);

        return response()->json(null, 204);
    }
}
