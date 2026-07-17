<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Operator;
use App\Models\TreasuryPayment;

class OperatorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Operator::where('zombie', 0)
                         ->where('role_id', 8);

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
            'email' => 'required|email|unique:users',
            'password' => [
                'required',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,15}$/',
                'confirmed'
            ]
        ], [
            'password.regex' => 'La contraseña debe tener entre 8 y 15 caracteres, e incluir al menos una mayúscula, una minúscula, un número y un símbolo (@$!%*?&).'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $data['role_id'] = 8;

        $operator = Operator::create($data);

        return response()->json($operator, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Operator::where('role_id', 8)->where('id',$id)->first();
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
            'email' => 'required|email'
        ]);


        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        Operator::find($id)->update($data);

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
        Operator::find($id)->update(['zombie' => 1]);

        return response()->json(null, 204);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function payments(Request $request, $id) {
        
        $result = TreasuryPayment::with('operator')
                                ->where('operator_id', $id)
                                ->where('paid', 1)
                                ->where('zombie', 0)
                                ->orderBy('id', 'desc')
                                ->get();

        return $result;
        
    }
}
